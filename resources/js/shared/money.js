/**
 * Format a decimal string/number from the API as Indonesian Rupiah, e.g. "Rp 1.250.000".
 * Fraction digits only show when the amount has them.
 */
const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

export function formatMoney(value) {
    return rupiah.format(Number(value));
}
