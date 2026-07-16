<template>
    <div class="border-2 border-amber-200 rounded-xl bg-white overflow-hidden focus-within:ring-4 focus-within:ring-amber-500/20 focus-within:border-amber-500 transition-all duration-200">
        <div v-if="editor" class="flex items-center gap-1 px-2 py-1.5 border-b border-slate-100 bg-slate-50 flex-wrap">
            <button type="button" @click="editor.chain().focus().toggleBold().run()"
                :class="editor.isActive('bold') ? 'bg-amber-100 text-amber-700' : 'text-slate-500 hover:bg-slate-100'"
                class="p-1.5 rounded-lg transition-colors" title="Negrita">
                <Bold class="w-4 h-4" />
            </button>
            <button type="button" @click="editor.chain().focus().toggleItalic().run()"
                :class="editor.isActive('italic') ? 'bg-amber-100 text-amber-700' : 'text-slate-500 hover:bg-slate-100'"
                class="p-1.5 rounded-lg transition-colors" title="Cursiva">
                <Italic class="w-4 h-4" />
            </button>
            <button type="button" @click="editor.chain().focus().toggleUnderline().run()"
                :class="editor.isActive('underline') ? 'bg-amber-100 text-amber-700' : 'text-slate-500 hover:bg-slate-100'"
                class="p-1.5 rounded-lg transition-colors" title="Subrayado">
                <UnderlineIcon class="w-4 h-4" />
            </button>
            <div class="w-px h-5 bg-slate-200 mx-1"></div>
            <button type="button" @click="editor.chain().focus().toggleBulletList().run()"
                :class="editor.isActive('bulletList') ? 'bg-amber-100 text-amber-700' : 'text-slate-500 hover:bg-slate-100'"
                class="p-1.5 rounded-lg transition-colors" title="Lista con viñetas">
                <List class="w-4 h-4" />
            </button>
            <button type="button" @click="toggleLink"
                :class="editor.isActive('link') ? 'bg-amber-100 text-amber-700' : 'text-slate-500 hover:bg-slate-100'"
                class="p-1.5 rounded-lg transition-colors" title="Enlace">
                <Link2 class="w-4 h-4" />
            </button>
            <div class="w-px h-5 bg-slate-200 mx-1"></div>
            <div ref="emojiWrapperRef">
                <button type="button" ref="emojiButtonRef" @click="toggleEmojiPicker"
                    :class="showEmojiPicker ? 'bg-amber-100 text-amber-700' : 'text-slate-500 hover:bg-slate-100'"
                    class="p-1.5 rounded-lg transition-colors" title="Insertar emoji">
                    <Smile class="w-4 h-4" />
                </button>
            </div>
        </div>
        <EditorContent :editor="editor" class="tiptap-content px-4 py-3 text-sm text-slate-900 min-h-[220px] max-h-[420px] overflow-y-auto" />

        <Teleport to="body">
            <div v-if="showEmojiPicker" ref="emojiPanelRef" :style="emojiPanelStyle"
                class="fixed z-[9999] w-64 max-h-56 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-xl p-2">
                <div v-if="recentEmojis.length" class="mb-2">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-1 mb-1">Usados recientemente</p>
                    <div class="grid grid-cols-8 gap-0.5">
                        <button v-for="emoji in recentEmojis" :key="'recent-' + emoji" type="button" @click="insertEmoji(emoji)"
                            class="text-lg leading-none p-1 rounded-lg hover:bg-amber-50 transition-colors">
                            {{ emoji }}
                        </button>
                    </div>
                </div>
                <div v-for="group in EMOJI_GROUPS" :key="group.label" class="mb-2 last:mb-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-1 mb-1">{{ group.label }}</p>
                    <div class="grid grid-cols-8 gap-0.5">
                        <button v-for="emoji in group.emojis" :key="emoji" type="button" @click="insertEmoji(emoji)"
                            class="text-lg leading-none p-1 rounded-lg hover:bg-amber-50 transition-colors">
                            {{ emoji }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import { Bold, Italic, Underline as UnderlineIcon, List, Link2, Smile } from 'lucide-vue-next';

const EMOJI_GROUPS = [
    {
        label: 'Caras y gestos',
        emojis: ['😀', '😃', '😄', '😊', '🙂', '😉', '😍', '🤩', '🥳', '👏', '🙌', '👍', '🤝', '✋', '👋', '💪'],
    },
    {
        label: 'Eventos y educación',
        emojis: ['🎓', '📚', '📖', '📝', '📅', '🗓️', '⏰', '📌', '📍', '💻', '🖥️', '🏆', '🎯', '📊', '🏫', '🧑‍🏫'],
    },
    {
        label: 'Símbolos',
        emojis: ['✅', '⭐', '🌟', '🔥', '💡', '📢', '🔔', '❗', '❓', '➡️', '✨', '🎉', '🎊', '👉', '💯', '❤️'],
    },
];

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit,
        Placeholder.configure({ placeholder: props.placeholder }),
    ],
    editorProps: {
        attributes: {
            class: 'focus:outline-none',
        },
    },
    onUpdate: ({ editor: editorInstance }) => {
        const html = editorInstance.isEmpty ? '' : editorInstance.getHTML();
        emit('update:modelValue', html);
    },
});

watch(() => props.modelValue, (value) => {
    if (!editor.value) return;
    const current = editor.value.isEmpty ? '' : editor.value.getHTML();
    if ((value || '') !== current) {
        editor.value.commands.setContent(value || '', { emitUpdate: false });
    }
});

const toggleLink = () => {
    if (!editor.value) return;
    if (editor.value.isActive('link')) {
        editor.value.chain().focus().unsetLink().run();
        return;
    }
    const url = window.prompt('URL del enlace:');
    if (url) {
        editor.value.chain().focus().setLink({ href: url }).run();
    }
};

const RECENT_EMOJIS_KEY = 'utilitarios_recent_emojis';
const MAX_RECENT_EMOJIS = 16;
const showEmojiPicker = ref(false);
const emojiWrapperRef = ref(null);
const emojiButtonRef = ref(null);
const emojiPanelRef = ref(null);
const emojiPanelStyle = ref({});
const recentEmojis = ref([]);

const positionEmojiPanel = () => {
    const buttonEl = emojiButtonRef.value;
    if (!buttonEl) return;
    const rect = buttonEl.getBoundingClientRect();
    const panelWidth = 256;
    const spaceBelow = window.innerHeight - rect.bottom;
    const openUpwards = spaceBelow < 240 && rect.top > 240;

    emojiPanelStyle.value = {
        left: `${Math.min(rect.left, window.innerWidth - panelWidth - 8)}px`,
        ...(openUpwards
            ? { bottom: `${window.innerHeight - rect.top + 4}px` }
            : { top: `${rect.bottom + 4}px` }),
    };
};

const toggleEmojiPicker = () => {
    showEmojiPicker.value = !showEmojiPicker.value;
    if (showEmojiPicker.value) {
        positionEmojiPanel();
    }
};

const loadRecentEmojis = () => {
    try {
        const stored = JSON.parse(localStorage.getItem(RECENT_EMOJIS_KEY) || '[]');
        if (Array.isArray(stored)) recentEmojis.value = stored;
    } catch (error) {
        recentEmojis.value = [];
    }
};

const rememberRecentEmoji = (emoji) => {
    const updated = [emoji, ...recentEmojis.value.filter((e) => e !== emoji)].slice(0, MAX_RECENT_EMOJIS);
    recentEmojis.value = updated;
    localStorage.setItem(RECENT_EMOJIS_KEY, JSON.stringify(updated));
};

const insertEmoji = (emoji) => {
    editor.value?.chain().focus().insertContent(emoji).run();
    rememberRecentEmoji(emoji);
    showEmojiPicker.value = false;
};

const handleClickOutside = (event) => {
    if (!showEmojiPicker.value) return;
    const clickedButton = emojiWrapperRef.value?.contains(event.target);
    const clickedPanel = emojiPanelRef.value?.contains(event.target);
    if (!clickedButton && !clickedPanel) {
        showEmojiPicker.value = false;
    }
};

const handleReposition = () => {
    if (showEmojiPicker.value) positionEmojiPanel();
};

onMounted(() => {
    loadRecentEmojis();
    document.addEventListener('mousedown', handleClickOutside);
    window.addEventListener('resize', handleReposition);
    window.addEventListener('scroll', handleReposition, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    window.removeEventListener('resize', handleReposition);
    window.removeEventListener('scroll', handleReposition, true);
    editor.value?.destroy();
});
</script>

<style scoped>
.tiptap-content :deep(.ProseMirror) {
    outline: none;
}

.tiptap-content :deep(p) {
    margin: 0 0 0.5em 0;
}

.tiptap-content :deep(p:last-child) {
    margin-bottom: 0;
}

.tiptap-content :deep(ul) {
    list-style: disc;
    padding-left: 1.25em;
    margin: 0 0 0.5em 0;
}

.tiptap-content :deep(a) {
    color: #d97706;
    text-decoration: underline;
}

.tiptap-content :deep(p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    color: #94a3b8;
    float: left;
    height: 0;
    pointer-events: none;
}
</style>
