import { EditorContent, useEditor, useEditorState } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Bold, Italic, Link2, Link2Off, List, ListOrdered } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

/**
 * Writing a description for a public page.
 *
 * Emphasis, links and lists, and deliberately nothing else. There is no font,
 * no size, no colour and no heading here, because the page those words land on
 * already decides all of that — and a toolbar offering otherwise is an
 * invitation to paste in something that fights the design.
 *
 * What that costs is narrower than it sounds: pasting from Word or a browser
 * drops everything the editor has no button for, headings and all, and the
 * server reduces whatever arrives to the same short vocabulary before it is
 * stored. So this toolbar is the honest account of what a description can be,
 * not merely the easy way to write one.
 */
export function RichTextEditor({
    id,
    label,
    value,
    onChange,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (html: string) => void;
}) {
    /*
     * The editor keeps its document outside React and reports changes through
     * callbacks rather than props, which the compiler cannot see; its own
     * documentation asks for this directive where the compiler is enabled.
     */
    'use no memo';

    const [linking, setLinking] = useState(false);
    const [href, setHref] = useState('');

    const editor = useEditor({
        // Rendered on the client only: the page is server-rendered first, and
        // a contenteditable has nothing to say until there is a document.
        immediatelyRender: false,
        extensions: [
            StarterKit.configure({
                heading: false,
                blockquote: false,
                codeBlock: false,
                code: false,
                strike: false,
                underline: false,
                horizontalRule: false,
                link: {
                    openOnClick: false,
                    autolink: true,
                    protocols: ['http', 'https', 'mailto', 'tel'],
                },
            }),
        ],
        content: value,
        editorProps: {
            attributes: {
                id,
                class: 'rich-text min-h-24 px-3 py-2 focus:outline-none',
                /*
                 * A label cannot point at a contenteditable, which is not a
                 * form control, so the box carries its own name.
                 */
                role: 'textbox',
                'aria-multiline': 'true',
                'aria-label': label,
            },
        },
        /*
         * An editor with the words deleted still holds a paragraph, and
         * sending that markup would store it as an edit. Reporting it as
         * empty is what makes clearing the box an undo.
         */
        onUpdate: ({ editor }) =>
            onChange(editor.getText().trim() === '' ? '' : editor.getHTML()),
    });

    /*
     * Typing in the document does not re-render this component, because none
     * of it is React state. The toolbar therefore reads what it needs through
     * a subscription, which is what keeps a button lit while the caret sits in
     * bold text and lets the link button know whether anything is selected.
     */
    const toolbar = useEditorState({
        editor,
        selector: ({ editor }) => ({
            bold: editor?.isActive('bold') ?? false,
            italic: editor?.isActive('italic') ?? false,
            bulletList: editor?.isActive('bulletList') ?? false,
            orderedList: editor?.isActive('orderedList') ?? false,
            onLink: editor?.isActive('link') ?? false,
            href: String(editor?.getAttributes('link').href ?? ''),
            hasSelection: editor ? !editor.state.selection.empty : false,
        }),
    });

    if (!editor || !toolbar) {
        return (
            <div className="min-h-32 animate-pulse rounded-md border border-input bg-muted/40" />
        );
    }

    function applyLink() {
        const target = href.trim();

        if (target === '') {
            editor?.chain().focus().unsetLink().run();
        } else {
            editor?.chain().focus().setLink({ href: target }).run();
        }

        setLinking(false);
        setHref('');
    }

    return (
        <div className="rounded-md border border-input bg-transparent shadow-xs focus-within:ring-2 focus-within:ring-ring">
            <div
                role="toolbar"
                aria-label="Formatting"
                aria-controls={id}
                className="flex flex-wrap items-center gap-0.5 border-b border-border/60 px-1.5 py-1"
            >
                <ToolbarButton
                    label="Bold"
                    active={toolbar.bold}
                    onClick={() => editor.chain().focus().toggleBold().run()}
                >
                    <Bold className="size-3.5" />
                </ToolbarButton>

                <ToolbarButton
                    label="Italic"
                    active={toolbar.italic}
                    onClick={() => editor.chain().focus().toggleItalic().run()}
                >
                    <Italic className="size-3.5" />
                </ToolbarButton>

                <span className="mx-1 h-5 w-px bg-border" />

                <ToolbarButton
                    label="Bulleted list"
                    active={toolbar.bulletList}
                    onClick={() =>
                        editor.chain().focus().toggleBulletList().run()
                    }
                >
                    <List className="size-3.5" />
                </ToolbarButton>

                <ToolbarButton
                    label="Numbered list"
                    active={toolbar.orderedList}
                    onClick={() =>
                        editor.chain().focus().toggleOrderedList().run()
                    }
                >
                    <ListOrdered className="size-3.5" />
                </ToolbarButton>

                <span className="mx-1 h-5 w-px bg-border" />

                <ToolbarButton
                    label={
                        toolbar.onLink
                            ? 'Change the link'
                            : toolbar.hasSelection
                              ? 'Add a link'
                              : 'Select some words to link them'
                    }
                    active={toolbar.onLink || linking}
                    disabled={!toolbar.onLink && !toolbar.hasSelection}
                    onClick={() => {
                        setHref(toolbar.href);
                        setLinking(!linking);
                    }}
                >
                    <Link2 className="size-3.5" />
                </ToolbarButton>

                {toolbar.onLink && (
                    <ToolbarButton
                        label="Remove the link"
                        onClick={() => {
                            editor.chain().focus().unsetLink().run();
                            setLinking(false);
                        }}
                    >
                        <Link2Off className="size-3.5" />
                    </ToolbarButton>
                )}
            </div>

            {linking && (
                <div className="flex items-center gap-2 border-b border-border/60 bg-muted/40 px-2 py-2">
                    <Input
                        autoFocus
                        value={href}
                        placeholder="https://example.org"
                        aria-label="Link address"
                        className="h-8 text-xs"
                        onChange={(event) => setHref(event.target.value)}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                applyLink();
                            }

                            if (event.key === 'Escape') {
                                setLinking(false);
                            }
                        }}
                    />
                    <Button type="button" size="sm" onClick={applyLink}>
                        Apply
                    </Button>
                </div>
            )}

            <EditorContent editor={editor} />
        </div>
    );
}

function ToolbarButton({
    label,
    active = false,
    disabled = false,
    onClick,
    children,
}: {
    label: string;
    active?: boolean;
    disabled?: boolean;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            title={label}
            aria-label={label}
            aria-pressed={active}
            disabled={disabled}
            /*
             * Pressing a toolbar button must not take the caret out of the
             * document, or the command would have no selection to act on.
             */
            onMouseDown={(event) => event.preventDefault()}
            onClick={onClick}
            className={cn(
                'inline-flex size-7 items-center justify-center rounded-sm text-muted-foreground transition-colors',
                'hover:bg-accent hover:text-accent-foreground',
                'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                'disabled:pointer-events-none disabled:opacity-40',
                active && 'bg-accent text-accent-foreground',
            )}
        >
            {children}
        </button>
    );
}
