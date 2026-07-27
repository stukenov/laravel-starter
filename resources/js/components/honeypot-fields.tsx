export type Honeypot = {
    enabled: boolean;
    nameFieldName: string;
    validFromFieldName: string;
    encryptedValidFrom: string;
};

/**
 * RULE(external): no third-party domain at render time — AGENTS.md.
 *
 * That rules out every captcha: the browser would block the script before
 * it loaded. What is left works with our own HTML only — a field nobody
 * can see, which a bot filling every input will fill too, and a signed
 * timestamp that rejects a form submitted faster than a person can type.
 *
 * `hidden` rather than a class, so it stays invisible even if the
 * stylesheet fails to load, and `aria-hidden` so a screen reader does not
 * offer it to somebody who would then be treated as a bot.
 */
export function HoneypotFields({ honeypot }: { honeypot: Honeypot }) {
    if (!honeypot.enabled) {
        return null;
    }

    return (
        <div hidden aria-hidden="true">
            <input
                type="text"
                name={honeypot.nameFieldName}
                defaultValue=""
                autoComplete="off"
                tabIndex={-1}
            />
            <input
                type="text"
                name={honeypot.validFromFieldName}
                defaultValue={honeypot.encryptedValidFrom}
                autoComplete="off"
                tabIndex={-1}
            />
        </div>
    );
}
