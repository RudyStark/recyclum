import { Controller } from '@hotwired/stimulus';

/*
 * Cookie Consent Controller - Conforme CNIL/RGPD
 * Gère le bandeau de consentement cookies au premier accès
 */
export default class extends Controller {
    static targets = ['banner'];

    static values = {
        consentDuration: { type: Number, default: 182 }, // 6 mois en jours (recommandation CNIL)
    };

    connect() {
        console.log('Cookie Consent Controller connected');
        this.checkConsent();
    }

    /**
     * Vérifie si l'utilisateur a déjà donné son consentement
     */
    checkConsent() {
        const consent = this.getConsent();

        if (!consent) {
            // Pas de consentement enregistré -> afficher le bandeau
            this.showBanner();
        } else {
            // Consentement déjà donné -> charger les cookies autorisés
            this.loadAuthorizedCookies(consent);
        }
    }

    /**
     * Récupère le consentement stocké
     */
    getConsent() {
        const consentCookie = this.getCookie('recyclum_cookie_consent');
        if (!consentCookie) return null;

        try {
            return JSON.parse(consentCookie);
        } catch (e) {
            console.error('Erreur parsing consent:', e);
            return null;
        }
    }

    /**
     * Affiche le bandeau de consentement
     */
    showBanner() {
        if (this.hasBannerTarget) {
            this.bannerTarget.classList.add('show');
            this.bannerTarget.setAttribute('aria-hidden', 'false');

            // Empêcher le scroll du body (optionnel)
            // document.body.style.overflow = 'hidden';
        }
    }

    /**
     * Cache le bandeau de consentement
     */
    hideBanner() {
        if (this.hasBannerTarget) {
            this.bannerTarget.classList.remove('show');
            this.bannerTarget.setAttribute('aria-hidden', 'true');

            // Réactiver le scroll
            // document.body.style.overflow = '';

            // Supprimer complètement après animation
            setTimeout(() => {
                this.bannerTarget.remove();
            }, 300);
        }
    }

    /**
     * Accepter tous les cookies
     */
    acceptAll(event) {
        event.preventDefault();

        const consent = {
            essential: true,      // Toujours true (obligatoires)
            analytics: true,
            marketing: true,
            functional: true,
            timestamp: Date.now(),
        };

        this.saveConsent(consent);
        this.loadAuthorizedCookies(consent);
        this.hideBanner();

        console.log('✅ Tous les cookies acceptés');
    }

    /**
     * Refuser les cookies optionnels
     */
    rejectAll(event) {
        event.preventDefault();

        const consent = {
            essential: true,      // Obligatoires uniquement
            analytics: false,
            marketing: false,
            functional: false,
            timestamp: Date.now(),
        };

        this.saveConsent(consent);
        this.loadAuthorizedCookies(consent);
        this.hideBanner();

        console.log('❌ Cookies optionnels refusés');
    }

    /**
     * Ouvrir le modal de personnalisation
     */
    customize(event) {
        event.preventDefault();

        // Toggle le modal de personnalisation
        const customizeModal = document.getElementById('cookieCustomizeModal');
        if (customizeModal) {
            customizeModal.classList.add('show');
            customizeModal.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }

    /**
     * Sauvegarder les préférences personnalisées
     */
    saveCustom(event) {
        event.preventDefault();

        // Récupérer les valeurs des checkboxes
        const consent = {
            essential: true, // Toujours true
            analytics: document.getElementById('cookie-analytics')?.checked || false,
            marketing: document.getElementById('cookie-marketing')?.checked || false,
            functional: document.getElementById('cookie-functional')?.checked || false,
            timestamp: Date.now(),
        };

        this.saveConsent(consent);
        this.loadAuthorizedCookies(consent);
        this.closeCustomizeModal();
        this.hideBanner();

        console.log('⚙️ Préférences personnalisées enregistrées:', consent);
    }

    /**
     * Fermer le modal de personnalisation
     */
    closeCustomizeModal() {
        const customizeModal = document.getElementById('cookieCustomizeModal');
        if (customizeModal) {
            customizeModal.classList.remove('show');
            customizeModal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    }

    /**
     * Sauvegarder le consentement dans un cookie
     */
    saveConsent(consent) {
        const consentString = JSON.stringify(consent);
        const expirationDays = this.consentDurationValue;

        this.setCookie('recyclum_cookie_consent', consentString, expirationDays);
    }

    /**
     * Charger les scripts/cookies selon les autorisations
     */
    loadAuthorizedCookies(consent) {
        // Cookies essentiels (toujours chargés)
        console.log('🔧 Cookies essentiels chargés');

        // Google Analytics
        if (consent.analytics) {
            this.loadGoogleAnalytics();
        }

        // Marketing (Google Ads, Facebook Pixel, etc.)
        if (consent.marketing) {
            this.loadMarketingScripts();
        }

        // Fonctionnels (préférences utilisateur, etc.)
        if (consent.functional) {
            this.loadFunctionalScripts();
        }
    }

    /**
     * Charger Google Analytics
     */
    loadGoogleAnalytics() {
        // Remplace 'G-XXXXXXXXXX' par ton ID Google Analytics
        const GA_ID = 'G-XXXXXXXXXX'; // À CONFIGURER

        if (document.querySelector(`script[src*="googletagmanager.com/gtag/js?id=${GA_ID}"]`)) {
            console.log('📊 Google Analytics déjà chargé');
            return;
        }

        // Charger le script Google Analytics
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`;
        document.head.appendChild(script);

        // Initialiser gtag
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', GA_ID, {
            'anonymize_ip': true, // Anonymisation IP (recommandé RGPD)
            'cookie_flags': 'SameSite=None;Secure'
        });

        console.log('📊 Google Analytics chargé');
    }

    /**
     * Charger les scripts marketing
     */
    loadMarketingScripts() {
        // Facebook Pixel
        // const FB_PIXEL_ID = 'XXXXXXXXXX'; // À CONFIGURER
        // if (!document.querySelector(`script[src*="connect.facebook.net"]`)) {
        //     !function(f,b,e,v,n,t,s)
        //     {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        //     n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        //     if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        //     n.queue=[];t=b.createElement(e);t.async=!0;
        //     t.src=v;s=b.getElementsByTagName(e)[0];
        //     s.parentNode.insertBefore(t,s)}(window, document,'script',
        //     'https://connect.facebook.net/en_US/fbevents.js');
        //     fbq('init', FB_PIXEL_ID);
        //     fbq('track', 'PageView');
        //     console.log('📢 Facebook Pixel chargé');
        // }

        console.log('📢 Scripts marketing chargés');
    }

    /**
     * Charger les scripts fonctionnels
     */
    loadFunctionalScripts() {
        // Charger les préférences utilisateur, widgets, etc.
        console.log('⚙️ Scripts fonctionnels chargés');
    }

    /**
     * Révoquer le consentement (pour bouton "Gérer les cookies" sur le site)
     */
    revoke(event) {
        event.preventDefault();

        // Supprimer le cookie de consentement
        this.deleteCookie('recyclum_cookie_consent');

        // Recharger la page pour réafficher le bandeau
        window.location.reload();
    }

    /**
     * Utilitaires pour gérer les cookies
     */
    setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = `expires=${date.toUTCString()}`;
        document.cookie = `${name}=${value};${expires};path=/;SameSite=Lax;Secure`;
    }

    getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    deleteCookie(name) {
        document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;`;
    }
}
