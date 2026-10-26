<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ResolutionsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        // Deliberately isolated: never refresh or migrate the user's MySQL database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        $migration = require database_path('migrations/2026_10_22_000001_create_resolution_archive_tables.php');
        $migration->up();
        $this->root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'resolutions-test-'.bin2hex(random_bytes(8));
        mkdir($this->root);
        config(['resolutions.uploads_root' => $this->root.'/uploads', 'resolutions.archive_root' => $this->root.'/archive']);
        mkdir($this->root.'/archive');
        $this->withoutMiddleware([EnsurePasswordIsChanged::class, HandleInertiaRequests::class]);
    }

    protected function tearDown(): void
    {
        if (isset($this->root) && str_starts_with(realpath($this->root), realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'resolutions-test-')) {
            File::deleteDirectory($this->root);
        }
        parent::tearDown();
    }

    private function user(string $role = 'ROL001', array $modules = []): User
    {
        $user = new User;
        $user->forceFill(['id' => 1, 'rol_id' => $role, 'modulos_json' => $modules]);
        $user->setRelation('customRole', null);
        return $user;
    }

    private function pdf(string $name = '00003 - 00053 RDR 2006.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    public function test_revision_is_preferred_in_either_upload_order_without_affecting_unique_files_or_other_folders(): void
    {
        $this->actingAs($this->user());
        $name = '00081 RDR 2026 ASUNTO.pdf';
        $revision = '00081 RDR 2026 ASUNTO[R].pdf';
        $this->postJson('/resoluciones', ['file'=>$this->pdf($name), 'folder'=>'2026'])->assertCreated();
        $this->postJson('/resoluciones', ['file'=>$this->pdf($revision), 'folder'=>'2026'])->assertCreated();
        $this->assertSame([$revision], DB::table('resolutions')->pluck('filename')->all());
        $this->assertFileDoesNotExist($this->root.'/uploads/2026/'.$name);
        $this->assertFileExists($this->root.'/uploads/2026/'.$revision);
        $this->postJson('/resoluciones', ['file'=>$this->pdf($name), 'folder'=>'2026'])->assertStatus(409);
        $this->postJson('/resoluciones', ['file'=>$this->pdf($name), 'folder'=>'otra'])->assertCreated();
        $this->postJson('/resoluciones', ['file'=>$this->pdf('00081 RDR 2026 OTRO ASUNTO.pdf'), 'folder'=>'2026'])->assertCreated();
        $this->postJson('/resoluciones', ['file'=>$this->pdf('00082 RDR 2026 UNICA.pdf'), 'folder'=>'2026'])->assertCreated();
        $this->assertSame(4, DB::table('resolutions')->count());
        $this->postJson('/resoluciones', ['file'=>$this->pdf('00081 RDR 2026 ASUNTO[R][R].pdf'), 'folder'=>'2026'])->assertCreated();
        $this->assertSame(5, DB::table('resolutions')->count());
        $this->assertFileExists($this->root.'/uploads/2026/'.$revision);
        $this->assertTrue(DB::table('resolutions')->where('filename', $revision)->exists());
        $this->postJson('/resoluciones', ['file'=>$this->pdf('00081 RDR 2026 ASUNTO[R][R].pdf'), 'folder'=>'solo-repetida'])->assertCreated();
        $this->postJson('/resoluciones', ['file'=>$this->pdf($name), 'folder'=>'solo-repetida'])->assertStatus(409);
        $this->assertSame(6, DB::table('resolutions')->count());
    }

    public function test_distinct_counts_and_repeated_filter_separate_years_and_series(): void
    {
        $this->actingAs($this->user());
        foreach (['00001 RDR 2026 ASUNTO A.pdf','00001 RDR 2026 ASUNTO B.pdf','00001 RD 2026 ASUNTO C.pdf','00001 RDR 2025 ASUNTO D.pdf'] as $name) {
            $this->postJson('/resoluciones', ['file'=>$this->pdf($name), 'folder'=>'Resoluciones'])->assertCreated();
        }
        $response = $this->get('/resoluciones?status=repeated&year=2026', ['X-Inertia'=>'true'])->assertOk()->assertJsonPath('props.records.total', 2);
        $this->assertSame(['ASUNTO A','ASUNTO B'], collect($response->json('props.records.data'))->pluck('title')->sort()->values()->all());
        $this->get('/resoluciones?status=repeated&year=2025', ['X-Inertia'=>'true'])->assertOk()->assertJsonPath('props.records.total', 0);
        $groups = collect($response->json('props.coverage.groups'));
        $rdr = $groups->first(fn($g) => $g['year']===2026 && $g['series']==='RDR');
        $this->assertSame(2, $rdr['files']);
        $this->assertSame(1, $rdr['unique']);
        $this->assertSame(1, $rdr['extra_files']);
        $this->assertSame(1, $rdr['repeated_numbers']);
    }

    public function test_upload_search_range_download_and_duplicate_protection(): void
    {
        $this->actingAs($this->user());
        $response = $this->postJson('/resoluciones', ['file' => $this->pdf(), 'folder' => '2006']);
        $response->assertCreated();
        $id = $response->json('id');
        $this->get('/resoluciones?year=2006&number=25', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.records.total', 1);
        $this->get('/resoluciones?number=54', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.records.total', 0);
        $this->get('/resoluciones?q=nonexistent', ['X-Inertia' => 'true'])->assertJsonPath('props.records.total', 0);
        $this->get('/resoluciones?q=%25', ['X-Inertia' => 'true'])->assertJsonPath('props.records.total', 0);
        $this->get("/resoluciones/$id/archivo")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->head("/resoluciones/$id/archivo")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get("/resoluciones/$id/archivo?download=1")->assertDownload('00003 - 00053 RDR 2006.pdf');
        $this->postJson('/resoluciones', ['file' => $this->pdf(), 'folder' => '2006'])->assertStatus(409);
        $this->assertSame(1, DB::table('resolutions')->count());
    }

    public function test_auxiliaries_are_not_saved_and_bulk_coverage_reports_missing_numbers(): void
    {
        $this->actingAs($this->user());
        foreach (['291 ACTUADOS RDR 2024.pdf','NOTIFICACIÓN ROI 0003-2025.pdf','c.pdf','cx.pdf'] as $name) {
            $this->postJson('/resoluciones',['file'=>$this->pdf($name),'folder'=>'2024'])->assertUnprocessable();
            $this->postJson('/resoluciones/desde-archivo-local',['filename'=>$name,'folder'=>'2024'])->assertUnprocessable();
        }
        $this->assertSame(0,DB::table('resolutions')->count());
        $this->assertSame(0,DB::table('resolution_folders')->count());
        foreach (['00001 RDR 2024.pdf','00003 RDR 2024.pdf','00005 - 00007 RDR 2024.pdf','00001 RDR 2026.pdf'] as $name) $this->postJson('/resoluciones',['file'=>$this->pdf($name)])->assertCreated();
        $this->getJson('/resoluciones/cobertura?year=2024')->assertOk()->assertJsonCount(1,'groups')->assertJsonPath('missing',2)->assertJsonPath('groups.0.gaps',[[2,2],[4,4]])->assertJsonPath('groups.0.last',7);
        $this->postJson('/resoluciones',['file'=>$this->pdf('00002 RDR 2024.pdf')])->assertCreated();
        $this->getJson('/resoluciones/cobertura?year=2024')->assertJsonPath('missing',1)->assertJsonPath('groups.0.gaps',[[4,4]]);
    }

    public function test_local_archive_recovers_long_paths_without_renaming_or_overwriting(): void
    {
        $this->actingAs($this->user());
        $folder = 'RDR DRE HCO. 2026/'.str_repeat('carpeta_', 8);
        $name = '00273 RDR 2026 '.str_repeat('RESOLUCION ', 18).'.pdf';
        $directory = $this->root.'/archive/'.$folder;
        mkdir($directory, 0750, true);
        $source = $directory.'/'.$name;
        $pdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF";
        file_put_contents($source, $pdf);
        $this->assertGreaterThan(260, strlen($source));
        $response = $this->postJson('/resoluciones/desde-archivo-local', ['folder' => $folder, 'filename' => $name, 'year' => 2026]);
        $this->assertSame(201, $response->status(), $response->getContent());
        $row = DB::table('resolutions')->first();
        $this->assertSame($name, $row->filename);
        $this->assertSame(strlen($pdf), (int) $row->size_bytes);
        $this->assertSame($pdf, file_get_contents($source));
        $this->assertSame($pdf, file_get_contents($this->root.'/uploads/'.$folder.'/'.$name));
        $this->postJson('/resoluciones/desde-archivo-local', ['folder' => $folder, 'filename' => $name])->assertStatus(409);
        $this->assertSame(1, DB::table('resolutions')->count());
    }

    public function test_completed_unreadable_file_is_a_duplicate_even_without_a_server_archive(): void
    {
        $this->actingAs($this->user());
        $name = '00273 RDR 2026 '.str_repeat('ASUNTO ', 25).'.pdf';
        $folder = '2026/'.str_repeat('a', 100).'/'.str_repeat('b', 100);
        $this->postJson('/resoluciones', ['file'=>$this->pdf($name),'folder'=>$folder,'year'=>2026])->assertCreated();
        $this->assertGreaterThan(290, strlen($this->root.'/uploads/'.$folder.'/'.$name));
        $this->assertFileExists($this->root.'/uploads/'.$folder.'/'.$name);
        config(['resolutions.archive_root'=>null]);
        $this->postJson('/resoluciones/desde-archivo-local', ['filename'=>$name,'folder'=>$folder,'year'=>2026])->assertStatus(409);
        $this->postJson('/resoluciones/desde-archivo-local', ['filename'=>'different.pdf','folder'=>$folder])
            ->assertUnprocessable()->assertJsonPath('code','LOCAL_SOURCE_UNREADABLE');
        $this->assertSame(1, DB::table('resolutions')->count());
        $this->assertSame($folder, DB::table('resolution_folders')->value('relative_path'));
        $this->assertSame($name, DB::table('resolutions')->value('filename'));
    }

    public function test_local_archive_requires_upload_permission_and_confines_source_paths(): void
    {
        $url = '/resoluciones/desde-archivo-local';
        $this->postJson($url, ['filename' => 'test.pdf'])->assertUnauthorized();
        $this->actingAs($this->user('ROL012'));
        $this->postJson($url, ['filename' => 'test.pdf'])->assertForbidden();
        $this->actingAs($this->user());
        $this->postJson($url, ['folder' => '../', 'filename' => 'test.pdf'])->assertUnprocessable();
        $this->postJson($url, ['filename' => '../test.pdf'])->assertUnprocessable();
        $this->postJson($url, ['filename' => 'absent.pdf'])->assertUnprocessable();
        file_put_contents($this->root.'/archive/not-pdf.pdf', 'invalid');
        $this->postJson($url, ['filename' => 'not-pdf.pdf'])->assertUnprocessable();
        config(['resolutions.archive_root' => null]);
        $this->postJson($url, ['filename' => 'test.pdf'])->assertUnprocessable();
        $this->assertSame(0, DB::table('resolutions')->count());
    }

    public function test_permissions_protect_catalog_upload_and_files(): void
    {
        $this->get('/resoluciones')->assertRedirect('/login');
        $this->actingAs($this->user('ROL012'));
        $this->get('/resoluciones')->assertRedirect('/dashboard');
        $this->postJson('/resoluciones', ['file' => $this->pdf()])->assertForbidden();
        $this->get('/resoluciones/1/archivo')->assertRedirect('/dashboard');
        $this->assertSame(0, DB::table('resolutions')->count());
        $this->actingAs($this->user('ROL012', ['resoluciones']));
        $this->get('/resoluciones', ['X-Inertia' => 'true'])->assertOk();
    }

    public function test_traversal_non_pdf_and_invalid_metadata_are_rejected(): void
    {
        $this->actingAs($this->user());
        $this->postJson('/resoluciones', ['file' => $this->pdf(), 'folder' => '../outside'])->assertUnprocessable();
        $this->postJson('/resoluciones', ['file' => $this->pdf(), 'folder' => 'C:/outside'])->assertUnprocessable();
        $this->postJson('/resoluciones', ['file' => UploadedFile::fake()->createWithContent('fake.pdf', 'not a pdf')])->assertUnprocessable();
        $id = $this->postJson('/resoluciones', ['file' => $this->pdf()])->json('id');
        $this->patchJson('/resoluciones/'.$id, ['series_id' => 1, 'year' => 2026, 'number_start' => 10, 'number_end' => 3])->assertUnprocessable();
        $this->patch('/resoluciones/'.$id, ['series_id' => 1, 'year' => 2026, 'number_start' => 10, 'number_end' => null])->assertRedirect();
        $this->assertSame(2026, DB::table('resolutions')->value('year'));
    }

    public function test_index_is_repeatable_keeps_corrections_and_does_not_copy_files(): void
    {
        $path = $this->root.'/archive';
        mkdir($path.'/RDR 2000');
        file_put_contents($path.'/RDR 2000/RDR-001.pdf', 'metadata-only indexing');
        file_put_contents($path.'/notes.txt', 'skip');
        $this->artisan('resoluciones:indexar')->assertSuccessful();
        $this->assertSame(1, DB::table('resolutions')->count());
        DB::table('resolutions')->update(['number_start' => 2]);
        $this->artisan('resoluciones:indexar')->assertSuccessful();
        $this->assertSame(1, DB::table('resolutions')->count());
        $this->assertSame(2, DB::table('resolutions')->value('number_start'));
        $this->assertDirectoryDoesNotExist($this->root.'/uploads');
        $this->actingAs($this->user());
        unlink($path.'/RDR 2000/RDR-001.pdf');
        $this->get('/resoluciones/'.DB::table('resolutions')->value('id').'/archivo')->assertNotFound();
    }

    public function test_future_years_are_rejected_and_typo_uses_annual_folder(): void
    {
        $this->actingAs($this->user());
        $future = now('America/Lima')->year + 1;
        $this->postJson('/resoluciones', ['file' => $this->pdf(), 'year' => $future])->assertUnprocessable();
        $id = $this->postJson('/resoluciones', ['file' => $this->pdf('02653 RDR 2034.pdf'), 'folder' => 'RDR DRE HCO. 2024'])->assertCreated()->json('id');
        $this->assertSame(2024, DB::table('resolutions')->where('id', $id)->value('year'));
        $this->patchJson('/resoluciones/'.$id, ['series_id' => 1, 'year' => $future, 'number_start' => 2653])->assertUnprocessable();
        $this->getJson('/resoluciones?year='.$future)->assertUnprocessable();
    }

    public function test_smart_search_canonical_pagination_and_grouped_filters(): void
    {
        $this->actingAs($this->user());
        $id = $this->postJson('/resoluciones', ['file' => $this->pdf(), 'folder' => '2006'])->assertCreated()->json('id');
        foreach (['25-2006', 'RDR 00025-2006', '00025 RDR 2006', '25'] as $query) {
            $this->get('/resoluciones?q='.urlencode($query), ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.records.total', 1)->assertJsonPath('props.filters.number', 25);
        }
        $this->get('/resoluciones?status=grouped', ['X-Inertia' => 'true'])->assertJsonPath('props.records.total', 1);
        $this->get('/resoluciones?status=review', ['X-Inertia' => 'true'])->assertJsonPath('props.records.total', 0);
        $this->getJson('/resoluciones?sort=unsafe')->assertUnprocessable();
        $this->getJson('/resoluciones?q=25-'.(now('America/Lima')->year + 1))->assertUnprocessable();
        $row = (array) DB::table('resolutions')->where('id', $id)->first();
        unset($row['id']);
        for ($i = 0; $i < 31; $i++) DB::table('resolutions')->insert([...$row, 'filename' => 'Copia de prueba '.$i.'.pdf']);
        \Illuminate\Support\Facades\Cache::forget('resolutions-year-counts-v2');
        $response = $this->get('/resoluciones?q=RDR%2025-2006', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.records.total', 32)->assertJsonPath('props.yearCounts.0.total', 32);
        $next = $response->json('props.records.next_page_url');
        $this->assertStringContainsString('number=25', $next);
        $this->assertStringContainsString('year=2006', $next);
        $this->assertStringNotContainsString('q=', $next);
        $this->get($next, ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.records.total', 32)->assertJsonCount(2, 'props.records.data');
    }

    public function test_drive_folders_filter_documents_and_preserve_pagination(): void
    {
        $this->actingAs($this->user());
        $id = $this->postJson('/resoluciones', ['file' => $this->pdf('001 RDR 2024.pdf'), 'folder' => 'Archivo/2024'])->assertCreated()->json('id');
        $this->postJson('/resoluciones', ['file' => $this->pdf('002 RDR 2025.pdf'), 'folder' => 'Archivo/2025'])->assertCreated();
        $row = (array) DB::table('resolutions')->where('id', $id)->first();
        $folderId = $row['folder_id'];
        unset($row['id']);
        for ($i = 0; $i < 31; $i++) DB::table('resolutions')->insert([...$row, 'filename' => 'Documento '.$i.'.pdf']);
        $response = $this->get('/resoluciones?folder='.$folderId, ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('props.records.total', 32)->assertJsonPath('props.currentFolder.path', 'Archivo/2024')
            ->assertJsonCount(2, 'props.folders')->assertJsonPath('props.folders.0.total', 32);
        foreach ($response->json('props.records.data') as $record) $this->assertSame($folderId, $record['folder_id']);
        $next = $response->json('props.records.next_page_url');
        $this->assertStringContainsString('folder='.$folderId, $next);
        $this->get($next, ['X-Inertia' => 'true'])->assertOk()->assertJsonCount(2, 'props.records.data');
        $this->get('/resoluciones?year=2025', ['X-Inertia' => 'true'])->assertOk()->assertJsonCount(1, 'props.folders')
            ->assertJsonPath('props.folders.0.path', 'Archivo/2025')->assertJsonPath('props.currentFolder', null);
        $this->getJson('/resoluciones?folder=999999')->assertUnprocessable();
    }

    public function test_unusual_names_do_not_block_valid_pdf_uploads(): void
    {
        $this->actingAs($this->user());
        foreach (['00172 RDRR 2026 DECLARAR.pdf', '00534 RER 2026 APROBAR.pdf', 'sin identificar.pdf'] as $name) {
            $id = $this->postJson('/resoluciones', ['file' => $this->pdf($name)])->assertCreated()->json('id');
            $record = DB::table('resolutions')->where('id', $id)->first();
            $this->assertSame($name, $record->filename);
            $this->assertNull($record->series_id);
            $this->get('/resoluciones/'.$id.'/archivo')->assertOk();
        }
        $this->get('/resoluciones?number=172&year=2026', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.records.total', 1)->assertJsonPath('props.records.data.0.title', 'DECLARAR');
        $this->get('/resoluciones?status=review', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.records.total', 3);
        $this->patch('/resoluciones/'.$id, ['series_id'=>null, 'year'=>2026, 'number_start'=>609, 'number_end'=>null])->assertRedirect();
        $this->assertSame(609, DB::table('resolutions')->where('id', $id)->value('number_start'));
        $this->assertNull(DB::table('resolutions')->where('id', $id)->value('series_id'));
        $this->patchJson('/resoluciones/'.$id, ['series_id'=>null, 'year'=>2026, 'number_start'=>null, 'number_end'=>700])->assertUnprocessable();
    }

    public function test_resolution_role_actions_are_enforced_without_changing_legacy_access(): void
    {
        $this->actingAs($this->user());
        $id = $this->postJson('/resoluciones', ['file'=>$this->pdf()])->assertCreated()->json('id');
        $role = new \App\Models\CustomRole;
        $role->forceFill(['rol_id'=>'ROL012', 'permisos_json'=>['resoluciones'=>['ver']]]);
        $reader = $this->user('ROL012');
        $reader->setRelation('customRole', $role);
        $this->actingAs($reader);
        $this->get('/resoluciones', ['X-Inertia'=>'true'])->assertOk()->assertJsonPath('props.access.ver', true)->assertJsonPath('props.access.subir', false)->assertJsonPath('props.access.editar', false);
        $this->get('/resoluciones/'.$id.'/archivo')->assertOk();
        $this->postJson('/resoluciones', ['file'=>$this->pdf('otro.pdf')])->assertForbidden();
        $this->patchJson('/resoluciones/'.$id, ['year'=>2024])->assertForbidden();
        $this->assertSame(2006, DB::table('resolutions')->where('id',$id)->value('year'));
        $this->assertSame(1, DB::table('resolutions')->count());
        $reader->modulos_json = ['resoluciones'];
        $this->postJson('/resoluciones', ['file'=>$this->pdf('otro.pdf')])->assertForbidden();
        $role->permisos_json = ['resoluciones'=>['ver','subir','editar']];
        $this->postJson('/resoluciones', ['file'=>$this->pdf('otro.pdf')])->assertCreated();
        $this->patchJson('/resoluciones/'.$id, ['year'=>2024])->assertRedirect();
        $this->assertSame(2024, DB::table('resolutions')->where('id',$id)->value('year'));
        $reader->modulos_json = ['visitas'];
        $this->get('/resoluciones')->assertRedirect('/dashboard');
        $reader->modulos_json = ['resoluciones'];
        $role->permisos_json = ['visitas'=>['ver']];
        $this->get('/resoluciones', ['X-Inertia'=>'true'])->assertOk()->assertJsonPath('props.access.subir', true);
        $role->permisos_json = ['resoluciones'=>[]];
        $this->get('/resoluciones')->assertRedirect('/dashboard');
    }

    public function test_role_updates_preserve_permissions_for_other_modules(): void
    {
        \Illuminate\Support\Facades\Schema::create('custom_roles', function ($table) {
            $table->string('rol_id')->primary(); $table->string('codigo'); $table->string('nombre')->unique();
            $table->text('descripcion')->nullable(); $table->integer('nivel_acceso'); $table->boolean('activo');
            $table->text('permisos_json')->nullable(); $table->timestamps();
        });
        $role = \App\Models\CustomRole::create(['rol_id'=>'ROL099','codigo'=>'PRUEBA','nombre'=>'Prueba','nivel_acceso'=>3,'activo'=>true,'permisos_json'=>['visitas'=>['ver','crear'],'planillas'=>['ver']]]);
        $admin = $this->user();
        $admin->setRelation('roles', new \Illuminate\Database\Eloquent\Collection([new \Spatie\Permission\Models\Role(['name'=>'admin','guard_name'=>'web'])]));
        $this->actingAs($admin);
        $payload=['nombre'=>'Prueba','nivel_acceso'=>3,'resolution_access'=>['subir']];
        $this->putJson('/users/roles/ROL099',$payload)->assertOk();
        $expected=['visitas'=>['ver','crear'],'planillas'=>['ver'],'resoluciones'=>['subir','ver']];
        $this->assertSame($expected,$role->fresh()->permisos_json);
        $this->putJson('/users/roles/ROL099',['nombre'=>'Prueba','nivel_acceso'=>4])->assertOk();
        $this->assertSame($expected,$role->fresh()->permisos_json);
        $this->putJson('/users/roles/ROL099',[...$payload,'resolution_access'=>['eliminar']])->assertUnprocessable();
        $this->assertSame($expected,$role->fresh()->permisos_json);
        $this->putJson('/users/roles/ROL099',[...$payload,'resolution_access'=>[]])->assertOk();
        $this->assertSame([], $role->fresh()->permisos_json['resoluciones']);
        $this->putJson('/users/roles/ROL099',[...$payload,'resolution_access'=>null])->assertOk();
        $this->assertSame(['visitas'=>['ver','crear'],'planillas'=>['ver']],$role->fresh()->permisos_json);
    }

    public function test_annual_dashboard_counts_refresh_after_upload_and_identification(): void
    {
        $this->actingAs($this->user());
        $year = now('America/Lima')->year;
        $this->postJson('/resoluciones',['file'=>$this->pdf('00001 RDR '.$year.' APROBAR.pdf')])->assertCreated();
        $id = $this->postJson('/resoluciones',['file'=>$this->pdf('pendiente.pdf'),'year'=>$year])->assertCreated()->json('id');
        $response = $this->get('/resoluciones',['X-Inertia'=>'true'])->assertOk()->assertJsonPath('props.currentYear',$year)
            ->assertJsonPath('props.yearCounts.0.total',2)->assertJsonPath('props.yearCounts.0.review',1);
        $types = collect($response->json('props.annualBySeries'))->keyBy(fn($row) => $row['series'] ?? 'unknown');
        $this->assertSame(1, $types['RDR']['total']);
        $this->assertSame(1, $types['unknown']['total']);
        $this->get('/resoluciones?year='.$year.'&status=untyped',['X-Inertia'=>'true'])->assertOk()->assertJsonPath('props.records.total',1)
            ->assertJsonPath('props.records.data.0.id',$id);
        $this->patch('/resoluciones/'.$id,['series_id'=>1,'year'=>$year,'number_start'=>2,'number_end'=>null])->assertRedirect();
        $this->get('/resoluciones',['X-Inertia'=>'true'])->assertOk()->assertJsonPath('props.yearCounts.0.review',0)
            ->assertJsonCount(1,'props.annualBySeries')->assertJsonPath('props.annualBySeries.0.series','RDR')->assertJsonPath('props.annualBySeries.0.total',2);
        $this->postJson('/resoluciones',['file'=>$this->pdf('sinfecha.pdf')])->assertCreated();
        $response = $this->get('/resoluciones',['X-Inertia'=>'true'])->assertOk()->assertJsonPath('props.summary.total',3)
            ->assertJsonCount(1,'props.yearCounts')->assertJsonPath('props.yearCounts.0.total',2);
        $this->assertSame(3,collect($response->json('props.annualBySeries'))->sum('total'));
        $this->assertSame(1,collect($response->json('props.annualBySeries'))->firstWhere('year',null)['total']);
    }
}
