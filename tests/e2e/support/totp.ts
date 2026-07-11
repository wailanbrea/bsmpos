import { createHmac } from 'node:crypto';

/**
 * Genera un código TOTP (RFC 6238) a partir de un secreto en Base32.
 * Espejo en JS de `App\Core\Security\TotpService` para que las pruebas E2E
 * puedan resolver el segundo factor sin depender del backend.
 */
const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
const PERIOD = 30;
const DIGITS = 6;

function base32Decode(secret: string): Buffer {
    const clean = secret.replace(/=+$/, '').replace(/\s+/g, '').toUpperCase();
    let bits = '';

    for (const char of clean) {
        const index = BASE32_ALPHABET.indexOf(char);
        if (index === -1) {
            throw new Error(`Carácter Base32 inválido: ${char}`);
        }
        bits += index.toString(2).padStart(5, '0');
    }

    const bytes: number[] = [];
    for (let i = 0; i + 8 <= bits.length; i += 8) {
        bytes.push(parseInt(bits.slice(i, i + 8), 2));
    }

    return Buffer.from(bytes);
}

export function totpCode(secret: string, timestamp: number = Date.now()): string {
    const counter = Math.floor(timestamp / 1000 / PERIOD);

    const counterBuffer = Buffer.alloc(8);
    counterBuffer.writeBigUInt64BE(BigInt(counter));

    const hmac = createHmac('sha1', base32Decode(secret)).update(counterBuffer).digest();
    const offset = hmac[hmac.length - 1] & 0x0f;
    const binary =
        ((hmac[offset] & 0x7f) << 24) |
        ((hmac[offset + 1] & 0xff) << 16) |
        ((hmac[offset + 2] & 0xff) << 8) |
        (hmac[offset + 3] & 0xff);

    return (binary % 10 ** DIGITS).toString().padStart(DIGITS, '0');
}
