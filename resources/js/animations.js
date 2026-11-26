const animateLanding = () => {
    const gsap = window.gsap;
    if (!gsap || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const hero = document.querySelector('.hero-copy');
    if (hero) gsap.from(hero.children, { y: 18, opacity: 0, duration: 0.65, stagger: 0.1, ease: 'power2.out' });
    const cards = document.querySelectorAll('.role-card');
    if (cards.length) gsap.from(cards, { y: 22, opacity: 0, duration: 0.55, stagger: 0.1, delay: 0.25, ease: 'power2.out' });
};
if (document.readyState === 'complete') animateLanding();
else window.addEventListener('load', animateLanding, { once: true });
