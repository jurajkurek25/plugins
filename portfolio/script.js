// Smooth scrolling pre navigáciu
document.addEventListener('DOMContentLoaded', function() {

    // Navigačné linky
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.section');

    // Smooth scroll pri kliknutí na navigáciu
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            // Odstráň aktívnu triedu zo všetkých linkov
            navLinks.forEach(l => l.classList.remove('active'));

            // Pridaj aktívnu triedu na kliknutý link
            this.classList.add('active');

            // Získaj cieľovú sekciu
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);

            // Scroll k sekcii
            if (targetSection) {
                const offsetTop = targetSection.offsetTop - 100;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Sledovanie scrollu pre aktívnu sekciu
    function updateActiveLink() {
        let current = '';

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;

            if (window.pageYOffset >= sectionTop - 150) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    }

    // Sleduj scroll
    window.addEventListener('scroll', updateActiveLink);

    // Animácia pre sekcie pri scrollovaní
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Pozoruj všetky sekcie
    sections.forEach(section => {
        observer.observe(section);
    });

    // Parallax efekt pre ľavú fotografiu (jemný)
    const leftSection = document.querySelector('.left-section');
    if (leftSection) {
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallax = scrolled * 0.3;
            leftSection.style.transform = `translateY(${parallax}px)`;
        });
    }

    // Hover efekt pre produktové karty
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s ease';
        });
    });

    // Kliknuteľné hodnoty
    const valueItems = document.querySelectorAll('.values li');
    valueItems.forEach(item => {
        item.addEventListener('click', function() {
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.15)';
            setTimeout(() => {
                this.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
            }, 300);
        });
    });

    // Animácia pre nadpis pri načítaní
    const mainTitle = document.querySelector('.main-title');
    if (mainTitle) {
        setTimeout(() => {
            mainTitle.style.opacity = '0';
            mainTitle.style.transform = 'translateX(-30px)';

            setTimeout(() => {
                mainTitle.style.transition = 'all 0.8s ease';
                mainTitle.style.opacity = '1';
                mainTitle.style.transform = 'translateX(0)';
            }, 100);
        }, 200);
    }

    // Kontrola viditeľnosti elementov
    function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    // Fade-in efekt pre produktové karty
    function checkProductCards() {
        productCards.forEach((card, index) => {
            if (isInViewport(card)) {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateX(0)';
                }, index * 100);
            }
        });
    }

    // Nastavenie počiatočného stavu pre produktové karty
    productCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateX(-20px)';
        card.style.transition = 'all 0.5s ease';
    });

    window.addEventListener('scroll', checkProductCards);
    window.addEventListener('load', checkProductCards);

    // Klik na logo/meno scrolluje hore
    const mainTitleClickable = document.querySelector('.main-title');
    if (mainTitleClickable) {
        mainTitleClickable.style.cursor = 'pointer';
        mainTitleClickable.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Console log pre debug (môžete odstrániť)
    console.log('Portfolio Juraj Augustín Kurek načítané úspešne!');
    console.log('Počet sekcií:', sections.length);
    console.log('Počet produktov:', productCards.length);
});

// Easter egg - konami kód
let konamiCode = [];
const konamiPattern = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];

document.addEventListener('keydown', function(e) {
    konamiCode.push(e.key);
    konamiCode = konamiCode.slice(-10);

    if (konamiCode.join(',') === konamiPattern.join(',')) {
        document.body.style.background = 'linear-gradient(45deg, #000000, #1a1a1a, #333333)';
        document.body.style.backgroundSize = '400% 400%';
        document.body.style.animation = 'gradientShift 10s ease infinite';

        // Vytvor CSS animáciu
        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes gradientShift {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
        `;
        document.head.appendChild(style);

        console.log('🎉 Easter egg aktivovaný!');

        // Reset po 10 sekundách
        setTimeout(() => {
            document.body.style.background = 'var(--black)';
            document.body.style.animation = 'none';
        }, 10000);
    }
});
