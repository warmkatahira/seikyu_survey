/**
 * Page-wide motions (see the motion section of app.css): success messages that fold away on
 * their own, and totals that count up from zero.
 */

const FLASH_VISIBLE_MS = 5000;
const COUNT_UP_MS = 900;

/**
 * A success message (`[data-flash-autohide]`) stays a few seconds, then fades and folds its
 * height away, so the page below closes up smoothly instead of jumping.
 */
function autohideFlashes() {
    document.querySelectorAll('[data-flash-autohide]').forEach((flash) => {
        setTimeout(() => {
            const { height, marginBottom } = getComputedStyle(flash);
            const fold = flash.animate(
                [
                    { opacity: 1, height, marginBottom, overflow: 'hidden' },
                    { opacity: 0, height: '0px', marginBottom: '0px', paddingTop: '0px', paddingBottom: '0px', borderWidth: '0px', overflow: 'hidden' },
                ],
                { duration: 450, easing: 'ease-in' },
            );

            fold.onfinish = () => flash.remove();
        }, FLASH_VISIBLE_MS);
    });
}

/**
 * A number marked `data-count-up` counts from zero to the figure it shows, easing out at the
 * end. It is rendered with its final figure, so it reads right without this script.
 */
function countUp() {
    const formatter = new Intl.NumberFormat('ja-JP');

    document.querySelectorAll('[data-count-up]').forEach((element) => {
        const target = Number(element.textContent.replace(/[^\d]/g, ''));

        if (! Number.isFinite(target) || target === 0) {
            return;
        }

        const start = performance.now();
        const step = (now) => {
            const progress = Math.min(1, (now - start) / COUNT_UP_MS);
            element.textContent = formatter.format(Math.round(target * (1 - (1 - progress) ** 3)));

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };

        element.textContent = '0';
        requestAnimationFrame(step);
    });
}

export function initMotion() {
    autohideFlashes();
    countUp();
}
