import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Italic,
    List,
    ListOrdered,
    Quote,
    Redo,
    Undo,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

/**
 * RULE(html): formatted text is cleaned on the way in, once — AGENTS.md.
 *
 * The editor is not a security boundary and is not treated as one. What
 * it produces is a suggestion; the server decides what is stored, through
 * `SanitizedHtml`. Anybody can post whatever they like straight to the
 * endpoint, so a toolbar that offers only safe formatting proves nothing
 * — it is there because it is pleasant to use.
 *
 * The value reaches the form through a hidden input rather than through
 * React state, because the pages here submit real forms: `<Form>` reads
 * the DOM. That keeps this component usable anywhere without threading a
 * value and an onChange through every page that has one.
 */
type Props = {
    name: string;
    defaultValue?: string | null;
    id?: string;
    'data-test'?: string;
};

export default function RichText({
    name,
    defaultValue,
    id,
    'data-test': dataTest,
}: Props) {
    // Held in state and written by `onUpdate`, not read off the editor
    // during render. The editor object is stable, so a component that
    // merely called `getHTML()` never re-rendered on a keystroke and the
    // hidden input kept whatever it started with — the form submitted the
    // old document while the screen showed the new one. Only the browser
    // test caught that.
    const [html, setHtml] = useState(defaultValue ?? '');

    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                // No headings above h2: the page already has an h1, and a
                // second one written by an author breaks the outline for
                // anybody reading with a screen reader.
                heading: { levels: [2, 3, 4] },
                // Dropped rather than styled: neither survives the
                // server's allowlist, so offering them would mean showing
                // a person formatting that quietly disappears on save.
                codeBlock: false,
                horizontalRule: false,
            }),
        ],
        content: defaultValue ?? '',
        onUpdate: ({ editor }) => setHtml(editor.getHTML()),
        editorProps: {
            attributes: {
                class: 'prose-editor min-h-32 px-3 py-2 outline-none',
                'data-test': dataTest ? `${dataTest}-surface` : '',
            },
        },
    });

    const tools = [
        { icon: Bold, action: 'toggleBold', active: 'bold', label: 'bold' },
        {
            icon: Italic,
            action: 'toggleItalic',
            active: 'italic',
            label: 'italic',
        },
        {
            icon: List,
            action: 'toggleBulletList',
            active: 'bulletList',
            label: 'bullet list',
        },
        {
            icon: ListOrdered,
            action: 'toggleOrderedList',
            active: 'orderedList',
            label: 'ordered list',
        },
        {
            icon: Quote,
            action: 'toggleBlockquote',
            active: 'blockquote',
            label: 'quote',
        },
    ] as const;

    return (
        <div className="overflow-hidden rounded-md border">
            <div className="flex flex-wrap items-center gap-0.5 border-b bg-muted/40 p-1">
                {tools.map((tool) => (
                    <Button
                        key={tool.action}
                        type="button"
                        variant="ghost"
                        size="sm"
                        aria-label={tool.label}
                        aria-pressed={editor?.isActive(tool.active) ?? false}
                        className={cn(
                            'size-11 p-0 sm:size-7',
                            editor?.isActive(tool.active) && 'bg-background',
                        )}
                        onClick={() =>
                            editor?.chain().focus()[tool.action]().run()
                        }
                    >
                        <tool.icon className="size-4" />
                    </Button>
                ))}

                <span className="ml-auto flex items-center gap-0.5">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        aria-label="undo"
                        className="size-11 p-0 sm:size-7"
                        onClick={() => editor?.chain().focus().undo().run()}
                    >
                        <Undo className="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        aria-label="redo"
                        className="size-11 p-0 sm:size-7"
                        onClick={() => editor?.chain().focus().redo().run()}
                    >
                        <Redo className="size-4" />
                    </Button>
                </span>
            </div>

            <EditorContent editor={editor} id={id} />

            {/* What is actually submitted. The editor writes here on every
                change so a plain form post carries the document. */}
            <input
                type="hidden"
                name={name}
                data-test={dataTest}
                value={html}
                readOnly
            />
        </div>
    );
}
