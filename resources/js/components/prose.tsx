import { cn } from '@/lib/utils';

/**
 * A description written in the editor, printed wherever it is read.
 *
 * The markup is set directly because it is markup by the time it gets here,
 * and it is safe to set because nothing reaches this component that has not
 * been reduced to paragraphs, emphasis, links and lists by the sanitiser on
 * the server — on the way in when it was saved, and again on the way out.
 *
 * It carries the typographic classes the page would have put on its own
 * paragraph, so a block that nobody has touched looks exactly as it did
 * before it became editable.
 */
export function Prose({
    html,
    className,
    style,
}: {
    html: string;
    className?: string;
    /** For the staggered delays the opening band animates its lines with. */
    style?: React.CSSProperties;
}) {
    if (html.trim() === '') {
        return null;
    }

    return (
        <div
            className={cn('rich-text', className)}
            style={style}
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}
