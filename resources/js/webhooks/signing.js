/** Values covered by the signature, in signing order. */
export const SIGNED_FIELDS = ['timestamp', 'nonce', 'type', 'account_name', 'category_name', 'amount', 'note', 'recorded_at'];

export function toHex(buffer) {
    return [...new Uint8Array(buffer)].map((byte) => byte.toString(16).padStart(2, '0')).join('');
}

/** The hex HMAC-SHA256 of the signed fields as name=value pairs joined with &. */
export async function sign(fields, token) {
    const text = SIGNED_FIELDS.map((name) => `${name}=${fields[name] ?? ''}`).join('&');
    const key = await crypto.subtle.importKey('raw', new TextEncoder().encode(token), { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);

    return toHex(await crypto.subtle.sign('HMAC', key, new TextEncoder().encode(text)));
}

/** A fresh 16-byte random nonce as hex. */
export function newNonce() {
    return toHex(crypto.getRandomValues(new Uint8Array(16)));
}
