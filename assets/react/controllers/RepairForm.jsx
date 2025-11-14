import React, { useState, useEffect } from 'react';

// Liste des catégories d'appareils avec icônes Bootstrap Icons
const CATEGORIES = [
    {
        id: 'lave-linge',
        name: 'Lave-linge',
        icon: 'bi-moisture',
        description: 'Machine à laver, sèche-linge'
    },
    {
        id: 'refrigerateur',
        name: 'Réfrigérateur',
        icon: 'bi-snow',
        description: 'Frigo, congélateur, combi'
    },
    {
        id: 'four',
        name: 'Four & Cuisson',
        icon: 'bi-fire',
        description: 'Four, cuisinière, micro-ondes'
    },
    {
        id: 'lave-vaisselle',
        name: 'Lave-vaisselle',
        icon: 'bi-cup-hot',
        description: 'Tous types de lave-vaisselle'
    },
    {
        id: 'petit-electromenager',
        name: 'Petit électroménager',
        icon: 'bi-cup',
        description: 'Cafetière, bouilloire, robot'
    },
    {
        id: 'autre',
        name: 'Autre appareil',
        icon: 'bi-tools',
        description: 'Autre type d\'appareil'
    }
];

// Problèmes courants par catégorie
const COMMON_ISSUES = {
    'lave-linge': [
        'Ne démarre pas',
        'Fuite d\'eau',
        'Ne vidange pas',
        'Bruit anormal',
        'Ne chauffe pas',
        'Essorage défaillant',
        'Porte bloquée',
        'Autre problème'
    ],
    'refrigerateur': [
        'Ne refroidit pas assez',
        'Trop de givre',
        'Bruit anormal',
        'Fuite d\'eau',
        'Compresseur défaillant',
        'Porte ne ferme pas',
        'Éclairage défectueux',
        'Autre problème'
    ],
    'four': [
        'Ne chauffe pas',
        'Chauffe mal',
        'Porte défectueuse',
        'Pyrolyse en panne',
        'Ventilateur bruyant',
        'Problème électrique',
        'Boutons cassés',
        'Autre problème'
    ],
    'lave-vaisselle': [
        'Ne démarre pas',
        'Fuite d\'eau',
        'Ne sèche pas',
        'Vaisselle mal lavée',
        'Bruit anormal',
        'Porte défectueuse',
        'Ne vidange pas',
        'Autre problème'
    ],
    'petit-electromenager': [
        'Ne s\'allume pas',
        'Fonctionne par intermittence',
        'Bruit anormal',
        'Fuite',
        'Surchauffe',
        'Autre problème'
    ],
    'autre': [
        'Problème électrique',
        'Problème mécanique',
        'Autre problème'
    ]
};

export default function RepairForm({ apiEndpoint = '/api/repair-requests', phone = '01 43 07 63 63' }) {
    // États du formulaire
    const [step, setStep] = useState(1);
    const [selectedCategory, setSelectedCategory] = useState(null);
    const [formData, setFormData] = useState({
        category: '',
        brand: '',
        model: '',
        issue: '',
        issueDetails: '',
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        address: '',
        zipCode: '',
        city: '',
        preferredDate: '',
        repairLocation: 'atelier',
        urgency: false
    });
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submitSuccess, setSubmitSuccess] = useState(false);
    const [submitError, setSubmitError] = useState(null);
    const [countdown, setCountdown] = useState(20);

    // Gestion du compte à rebours
    useEffect(() => {
        if (submitSuccess) {
            const timer = setInterval(() => {
                setCountdown((prev) => {
                    if (prev <= 1) {
                        clearInterval(timer);
                        window.location.href = '/';
                        return 0;
                    }
                    return prev - 1;
                });
            }, 1000);

            return () => clearInterval(timer);
        }
    }, [submitSuccess]);

    // Gestion du changement de catégorie
    const handleCategorySelect = (category) => {
        setSelectedCategory(category);
        setFormData({ ...formData, category: category.id });
        setStep(2);
    };

    // Gestion des changements de champs
    const handleInputChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData({
            ...formData,
            [name]: type === 'checkbox' ? checked : value
        });
    };

    // Validation étape 2
    const validateStep2 = () => {
        return formData.issue && formData.issueDetails.trim().length > 10;
    };

    // Validation étape 3
    const validateStep3 = () => {
        return (
            formData.firstName.trim() &&
            formData.lastName.trim() &&
            formData.email.trim() &&
            formData.phone.trim() &&
            (formData.repairLocation === 'atelier' || (formData.address.trim() && formData.zipCode.trim() && formData.city.trim()))
        );
    };

    // Navigation entre étapes
    const goToNextStep = () => {
        if (step === 2 && validateStep2()) {
            setStep(3);
        }
    };

    const goToPreviousStep = () => {
        if (step > 1) {
            setStep(step - 1);
        }
    };

    // Soumission du formulaire
    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!validateStep3()) {
            return;
        }

        setIsSubmitting(true);
        setSubmitError(null);

        try {
            const response = await fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            if (!response.ok) {
                throw new Error('Erreur lors de l\'envoi de la demande');
            }

            setSubmitSuccess(true);
            setCountdown(20); // Réinitialise le compte à rebours

        } catch (error) {
            console.error('Erreur:', error);
            setSubmitError('Une erreur est survenue. Veuillez réessayer ou nous appeler directement.');
        } finally {
            setIsSubmitting(false);
        }
    };

    // Affichage du succès avec compte à rebours
    if (submitSuccess) {
        return (
            <div className="success-message">
                <div className="success-icon">✅</div>
                <h3>Demande envoyée avec succès !</h3>
                <p>Nous avons bien reçu votre demande de réparation.</p>
                <p>Vous allez recevoir un email de confirmation dans quelques instants.</p>
                <p>Nous vous contacterons dans les 2 heures pour établir un diagnostic et vous proposer un devis gratuit.</p>

                <div className="redirect-timer">
                    Redirection automatique dans <strong>{countdown}</strong> secondes...
                </div>

                <button
                    onClick={() => window.location.href = '/home'}
                    className="btn-primary"
                >
                    Retour à l'accueil maintenant
                </button>

                <div style={{ marginTop: '20px', fontSize: '14px', color: '#6C7783' }}>
                    <i className="bi bi-telephone"></i> Besoin d'une réponse immédiate ?
                    <br />Appelez-nous au <strong>{phone}</strong>
                </div>
            </div>
        );
    }

    return (
        <div className="repair-form">
            {/* Indicateur de progression */}
            <div className="form-progress mb-4">
                <div className="progress-steps">
                    <div className={`progress-step ${step >= 1 ? 'active' : ''} ${step > 1 ? 'completed' : ''}`}>
                        <div className="step-circle">1</div>
                        <div className="step-label">Appareil</div>
                    </div>
                    <div className="progress-line"></div>
                    <div className={`progress-step ${step >= 2 ? 'active' : ''} ${step > 2 ? 'completed' : ''}`}>
                        <div className="step-circle">2</div>
                        <div className="step-label">Problème</div>
                    </div>
                    <div className="progress-line"></div>
                    <div className={`progress-step ${step >= 3 ? 'active' : ''}`}>
                        <div className="step-circle">3</div>
                        <div className="step-label">Contact</div>
                    </div>
                </div>
            </div>

            {/* ÉTAPE 1 : Sélection de la catégorie */}
            {step === 1 && (
                <div className="form-step">
                    <h3 className="h5 mb-4 text-center">Quel type d'appareil souhaitez-vous faire réparer ?</h3>
                    <div className="category-grid">
                        {CATEGORIES.map((category) => (
                            <button
                                key={category.id}
                                type="button"
                                className={`category-card ${selectedCategory?.id === category.id ? 'selected' : ''}`}
                                onClick={() => handleCategorySelect(category)}
                            >
                                <div className="category-icon">
                                    <i className={`bi ${category.icon}`}></i>
                                </div>
                                <div className="category-name">{category.name}</div>
                                <div className="category-desc">{category.description}</div>
                            </button>
                        ))}
                    </div>
                </div>
            )}

            {/* ÉTAPE 2 : Détails du problème */}
            {step === 2 && (
                <div className="form-step">
                    <div className="mb-4">
                        <button
                            type="button"
                            className="btn btn-sm btn-outline-secondary"
                            onClick={goToPreviousStep}
                        >
                            <i className="bi bi-arrow-left"></i> Retour
                        </button>
                    </div>

                    <h3 className="h5 mb-4">Décrivez le problème de votre {selectedCategory?.name}</h3>

                    <form>
                        <div className="row g-3">
                            {/* Marque */}
                            <div className="col-12 col-md-6">
                                <label htmlFor="brand" className="form-label">
                                    Marque <span className="text-soft">(optionnel)</span>
                                </label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="brand"
                                    name="brand"
                                    value={formData.brand}
                                    onChange={handleInputChange}
                                    placeholder="Ex: Bosch, Samsung, Whirlpool..."
                                />
                            </div>

                            {/* Modèle */}
                            <div className="col-12 col-md-6">
                                <label htmlFor="model" className="form-label">
                                    Modèle <span className="text-soft">(optionnel)</span>
                                </label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="model"
                                    name="model"
                                    value={formData.model}
                                    onChange={handleInputChange}
                                    placeholder="Numéro de modèle"
                                />
                            </div>

                            {/* Type de problème */}
                            <div className="col-12">
                                <label htmlFor="issue" className="form-label">
                                    Type de problème <span className="text-danger">*</span>
                                </label>
                                <select
                                    className="form-select"
                                    id="issue"
                                    name="issue"
                                    value={formData.issue}
                                    onChange={handleInputChange}
                                    required
                                >
                                    <option value="">Sélectionnez un problème</option>
                                    {COMMON_ISSUES[selectedCategory?.id]?.map((issue) => (
                                        <option key={issue} value={issue}>
                                            {issue}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Description détaillée */}
                            <div className="col-12">
                                <label htmlFor="issueDetails" className="form-label">
                                    Description détaillée <span className="text-danger">*</span>
                                </label>
                                <textarea
                                    className="form-control"
                                    id="issueDetails"
                                    name="issueDetails"
                                    rows="4"
                                    value={formData.issueDetails}
                                    onChange={handleInputChange}
                                    placeholder="Décrivez précisément le problème : quand est-ce que ça se produit ? Y a-t-il des codes d'erreur ? Depuis combien de temps ? Plus vous donnez de détails, plus notre diagnostic sera précis."
                                    required
                                    minLength={10}
                                ></textarea>
                                <div className="form-text">
                                    Minimum 10 caractères ({formData.issueDetails.length}/10)
                                </div>
                            </div>

                            {/* Bouton suivant */}
                            <div className="col-12">
                                <button
                                    type="button"
                                    className="btn btn-primary btn-lg w-100"
                                    onClick={goToNextStep}
                                    disabled={!validateStep2()}
                                >
                                    Continuer <i className="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            )}

            {/* ÉTAPE 3 : Informations de contact */}
            {step === 3 && (
                <div className="form-step">
                    <div className="mb-4">
                        <button
                            type="button"
                            className="btn btn-sm btn-outline-secondary"
                            onClick={goToPreviousStep}
                        >
                            <i className="bi bi-arrow-left"></i> Retour
                        </button>
                    </div>

                    <h3 className="h5 mb-4">Vos coordonnées</h3>

                    {submitError && (
                        <div className="alert alert-danger mb-4">
                            <i className="bi bi-exclamation-triangle"></i> {submitError}
                        </div>
                    )}

                    <form onSubmit={handleSubmit}>
                        <div className="row g-3">
                            {/* Prénom */}
                            <div className="col-12 col-md-6">
                                <label htmlFor="firstName" className="form-label">
                                    Prénom <span className="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="firstName"
                                    name="firstName"
                                    value={formData.firstName}
                                    onChange={handleInputChange}
                                    required
                                />
                            </div>

                            {/* Nom */}
                            <div className="col-12 col-md-6">
                                <label htmlFor="lastName" className="form-label">
                                    Nom <span className="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="lastName"
                                    name="lastName"
                                    value={formData.lastName}
                                    onChange={handleInputChange}
                                    required
                                />
                            </div>

                            {/* Email */}
                            <div className="col-12 col-md-6">
                                <label htmlFor="email" className="form-label">
                                    Email <span className="text-danger">*</span>
                                </label>
                                <input
                                    type="email"
                                    className="form-control"
                                    id="email"
                                    name="email"
                                    value={formData.email}
                                    onChange={handleInputChange}
                                    required
                                />
                            </div>

                            {/* Téléphone */}
                            <div className="col-12 col-md-6">
                                <label htmlFor="phone" className="form-label">
                                    Téléphone <span className="text-danger">*</span>
                                </label>
                                <input
                                    type="tel"
                                    className="form-control"
                                    id="phone"
                                    name="phone"
                                    value={formData.phone}
                                    onChange={handleInputChange}
                                    placeholder="06 12 34 56 78"
                                    required
                                />
                            </div>

                            {/* Lieu de réparation */}
                            <div className="col-12">
                                <label className="form-label">
                                    Lieu de réparation <span className="text-danger">*</span>
                                </label>
                                <div className="repair-location-cards">
                                    <label className={`location-card ${formData.repairLocation === 'atelier' ? 'selected' : ''}`}>
                                        <input
                                            type="radio"
                                            name="repairLocation"
                                            value="atelier"
                                            checked={formData.repairLocation === 'atelier'}
                                            onChange={handleInputChange}
                                        />
                                        <div className="location-content">
                                            <div className="location-icon">
                                                <i className="bi bi-building"></i>
                                            </div>
                                            <div className="location-title">En atelier</div>
                                            <div className="location-desc">Vous apportez l'appareil</div>
                                            <div className="location-price">+ économique</div>
                                        </div>
                                    </label>
                                    <label className={`location-card ${formData.repairLocation === 'domicile' ? 'selected' : ''}`}>
                                        <input
                                            type="radio"
                                            name="repairLocation"
                                            value="domicile"
                                            checked={formData.repairLocation === 'domicile'}
                                            onChange={handleInputChange}
                                        />
                                        <div className="location-content">
                                            <div className="location-icon">
                                                <i className="bi bi-house-door"></i>
                                            </div>
                                            <div className="location-title">À domicile</div>
                                            <div className="location-desc">Nous venons chez vous</div>
                                            <div className="location-price">+ 45€ déplacement</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {/* Adresse (si domicile) */}
                            {formData.repairLocation === 'domicile' && (
                                <>
                                    <div className="col-12">
                                        <label htmlFor="address" className="form-label">
                                            Adresse <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            id="address"
                                            name="address"
                                            value={formData.address}
                                            onChange={handleInputChange}
                                            placeholder="Numéro et nom de rue"
                                            required
                                        />
                                    </div>
                                    <div className="col-12 col-md-4">
                                        <label htmlFor="zipCode" className="form-label">
                                            Code postal <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            id="zipCode"
                                            name="zipCode"
                                            value={formData.zipCode}
                                            onChange={handleInputChange}
                                            placeholder="75012"
                                            required
                                        />
                                    </div>
                                    <div className="col-12 col-md-8">
                                        <label htmlFor="city" className="form-label">
                                            Ville <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            id="city"
                                            name="city"
                                            value={formData.city}
                                            onChange={handleInputChange}
                                            placeholder="Paris"
                                            required
                                        />
                                    </div>
                                </>
                            )}

                            {/* Date préférée */}
                            <div className="col-12 col-md-6">
                                <label htmlFor="preferredDate" className="form-label">
                                    Date souhaitée <span className="text-soft">(optionnel)</span>
                                </label>
                                <input
                                    type="date"
                                    className="form-control"
                                    id="preferredDate"
                                    name="preferredDate"
                                    value={formData.preferredDate}
                                    onChange={handleInputChange}
                                    min={new Date().toISOString().split('T')[0]}
                                />
                            </div>

                            {/* Urgence */}
                            <div className="col-12 col-md-6">
                                <label className="form-label d-block mb-2">Options</label>
                                <div className="form-check form-switch">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id="urgency"
                                        name="urgency"
                                        checked={formData.urgency}
                                        onChange={handleInputChange}
                                    />
                                    <label className="form-check-label" htmlFor="urgency">
                                        <strong>Réparation urgente</strong>
                                        <div className="small text-soft">Intervention sous 24h si possible</div>
                                    </label>
                                </div>
                            </div>

                            {/* Soumission */}
                            <div className="col-12">
                                <button
                                    type="submit"
                                    className="btn btn-primary btn-lg w-100"
                                    disabled={!validateStep3() || isSubmitting}
                                >
                                    {isSubmitting ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Envoi en cours...
                                        </>
                                    ) : (
                                        <>
                                            <i className="bi bi-send"></i> Envoyer ma demande
                                        </>
                                    )}
                                </button>
                            </div>

                            <div className="col-12">
                                <div className="alert alert-light">
                                    <small>
                                        <i className="bi bi-shield-check"></i> Vos données sont protégées et utilisées uniquement pour traiter votre demande de réparation.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            )}
        </div>
    );
}
