import React, { useState, useEffect } from 'react';

// ============================================
// CATÉGORIES D'APPAREILS
// ============================================
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
        name: 'Four',
        icon: 'bi-fire',
        description: 'Four encastrable, cuisinière'
    },
    {
        id: 'lave-vaisselle',
        name: 'Lave-vaisselle',
        icon: 'bi-cup-hot',
        description: 'Tous types de lave-vaisselle'
    },
    {
        id: 'seche-linge',
        name: 'Sèche-linge',
        icon: 'bi-wind',
        description: 'Condensation, évacuation'
    },
    {
        id: 'cuisiniere',
        name: 'Cuisinière',
        icon: 'bi-grid-3x3',
        description: 'Cuisinière, piano de cuisson'
    },
    {
        id: 'micro-ondes',
        name: 'Micro-ondes',
        icon: 'bi-radioactive',
        description: 'Micro-ondes classique ou grill'
    },
    {
        id: 'cave-a-vin',
        name: 'Cave à vin',
        icon: 'bi-cup-straw',
        description: 'Cave de service ou vieillissement'
    },
    {
        id: 'hotte',
        name: 'Hotte',
        icon: 'bi-fan',
        description: 'Hotte aspirante, décor'
    },
    {
        id: 'petit-electromenager',
        name: 'Petit électroménager',
        icon: 'bi-cup',
        description: 'Robot, cafetière, bouilloire'
    }
];

// ============================================
// OPTIONS D'ANNÉE D'ACHAT
// ============================================
const PURCHASE_YEARS = [
    { value: '2024-2025', label: '2024-2025 (moins de 2 ans)' },
    { value: '2022-2023', label: '2022-2023 (2-3 ans)' },
    { value: '2020-2021', label: '2020-2021 (4-5 ans)' },
    { value: '2018-2019', label: '2018-2019 (6-7 ans)' },
    { value: '2015-2017', label: '2015-2017 (8-10 ans)' },
    { value: 'avant-2015', label: 'Avant 2015 (plus de 10 ans)' }
];

// ============================================
// ÉTATS FONCTIONNELS
// ============================================
const FUNCTIONAL_STATES = [
    {
        value: 'parfait',
        label: 'Fonctionne parfaitement',
        icon: '✅',
        description: 'Aucun défaut de fonctionnement',
        priceImpact: '100%'
    },
    {
        value: 'panne-legere',
        label: 'Panne légère',
        icon: '⚠️',
        description: 'Défaut mineur réparable',
        priceImpact: '60%'
    },
    {
        value: 'hors-service',
        label: 'Hors service',
        icon: '❌',
        description: 'Ne fonctionne plus',
        priceImpact: '20%'
    },
    {
        value: 'pieces',
        label: 'Pour pièces',
        icon: '🔩',
        description: 'Récupération de pièces',
        priceImpact: '10%'
    }
];

// ============================================
// ÉTATS ESTHÉTIQUES
// ============================================
const AESTHETIC_STATES = [
    {
        value: 'tres-bon',
        label: 'Très bon état',
        icon: '⭐',
        description: 'Comme neuf',
        priceImpact: '100%'
    },
    {
        value: 'bon',
        label: 'Bon état',
        icon: '✓',
        description: 'Traces d\'usage légères',
        priceImpact: '85%'
    },
    {
        value: 'usage',
        label: 'Usagé',
        icon: '📦',
        description: 'Rayures, bosses visibles',
        priceImpact: '65%'
    },
    {
        value: 'tres-usage',
        label: 'Très usagé',
        icon: '💔',
        description: 'Nombreux chocs et rayures',
        priceImpact: '40%'
    }
];

// ============================================
// COMPOSANT PRINCIPAL
// ============================================
export default function BuybackForm({ apiEndpoint = '/api/buyback-requests', phone = '01 43 07 63 63' }) {
    // États
    const [step, setStep] = useState(1);
    const [selectedCategory, setSelectedCategory] = useState(null);
    const [formData, setFormData] = useState({
        category: '',
        brand: '',
        model: '',
        purchaseYear: '',
        hasInvoice: false,
        functionalState: '',
        aestheticState: '',
        hasAllAccessories: true,
        additionalComments: '',
        photos: [],
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        address: '',
        zipCode: '',
        city: '',
        floor: '',
        hasElevator: false,
        paymentMethod: 'virement',
        iban: '',
        accountHolder: '',
        preferredDate: '',
        timeSlots: []
    });
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submitSuccess, setSubmitSuccess] = useState(false);
    const [submitError, setSubmitError] = useState(null);
    const [estimation, setEstimation] = useState(null);

    // Handlers
    const handleCategorySelect = (category) => {
        setSelectedCategory(category);
        setFormData({ ...formData, category: category.id });
        setStep(2);
    };

    const handleInputChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData({
            ...formData,
            [name]: type === 'checkbox' ? checked : value
        });
    };

    const handleTimeSlotChange = (slot) => {
        const newSlots = formData.timeSlots.includes(slot)
            ? formData.timeSlots.filter(s => s !== slot)
            : [...formData.timeSlots, slot];
        setFormData({ ...formData, timeSlots: newSlots });
    };

    const handlePhotoUpload = async (e) => {
        const files = Array.from(e.target.files);

        // Validation: max 5 photos
        if (formData.photos.length + files.length > 5) {
            alert('Maximum 5 photos autorisées');
            return;
        }

        // Convertir en base64
        const photoPromises = files.map(file => {
            return new Promise((resolve, reject) => {
                // Validation taille (5 Mo max)
                if (file.size > 5 * 1024 * 1024) {
                    alert(`${file.name} est trop volumineux (max 5 Mo)`);
                    reject();
                    return;
                }

                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        });

        try {
            const photos = await Promise.all(photoPromises);
            setFormData({
                ...formData,
                photos: [...formData.photos, ...photos]
            });
        } catch (error) {
            console.error('Erreur upload photos:', error);
        }
    };

    const removePhoto = (index) => {
        setFormData({
            ...formData,
            photos: formData.photos.filter((_, i) => i !== index)
        });
    };

    // Validations
    const validateStep2 = () => {
        return formData.brand.trim() && formData.purchaseYear;
    };

    const validateStep3 = () => {
        return formData.functionalState && formData.aestheticState;
    };

    const validateStep4 = () => {
        return formData.photos.length >= 2;
    };

    const validateStep5 = () => {
        const basicValid = formData.firstName.trim()
            && formData.lastName.trim()
            && formData.email.trim()
            && formData.phone.trim()
            && formData.address.trim()
            && formData.zipCode.trim()
            && formData.city.trim();

        if (!basicValid) return false;

        if (formData.paymentMethod === 'virement') {
            return formData.iban.trim() && formData.accountHolder.trim();
        }

        return true;
    };

    // Navigation
    const goToNextStep = () => {
        if (step === 2 && validateStep2()) setStep(3);
        if (step === 3 && validateStep3()) setStep(4);
        if (step === 4 && validateStep4()) setStep(5);
    };

    const goToPreviousStep = () => {
        if (step > 1) setStep(step - 1);
    };

    // Soumission
    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!validateStep5()) {
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

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Erreur lors de l\'envoi');
            }

            setEstimation(data.estimation);
            setSubmitSuccess(true);
            setCountdown(20);

        } catch (error) {
            console.error('Erreur:', error);
            setSubmitError(error.message || 'Une erreur est survenue. Veuillez réessayer.');
        } finally {
            setIsSubmitting(false);
        }
    };

    // Affichage succès
    if (submitSuccess && estimation) {
        return (
            <div className="success-message">
                <div className="success-icon">🎉</div>
                <h3>Estimation reçue !</h3>

                <div style={{
                    background: 'white',
                    padding: '2rem',
                    borderRadius: '12px',
                    margin: '2rem 0',
                    boxShadow: '0 4px 12px rgba(0,0,0,0.1)'
                }}>
                    <div style={{ fontSize: '0.9rem', color: '#6C7783', marginBottom: '1rem' }}>
                        💰 Fourchette de prix estimée
                    </div>
                    <div style={{ fontSize: '2.5rem', fontWeight: '900', color: '#16C669', marginBottom: '1.5rem' }}>
                        {estimation.min}€ - {estimation.max}€
                    </div>

                    {estimation.details && (
                        <div style={{ textAlign: 'left', fontSize: '0.9rem', color: '#6C7783' }}>
                            <div style={{ borderTop: '1px solid #E6EDF2', paddingTop: '1rem', marginBottom: '0.5rem' }}>
                                <strong>📊 Détail du calcul :</strong>
                            </div>
                            <div style={{ marginLeft: '1rem' }}>
                                <div>• Prix de base ({estimation.details.category_label} {estimation.details.brand}) : <strong>{estimation.details.base_price}€</strong></div>
                                <div>• Année ({estimation.details.year_label}) : <strong>{Math.round(estimation.details.year_coefficient * 100)}%</strong></div>
                                <div>• État fonctionnel ({estimation.details.functional_label}) : <strong>{Math.round(estimation.details.functional_coefficient * 100)}%</strong></div>
                                <div>• État esthétique ({estimation.details.aesthetic_label}) : <strong>{Math.round(estimation.details.aesthetic_coefficient * 100)}%</strong></div>
                                {estimation.details.invoice_bonus && <div>• Bonus facture : <strong>+10%</strong></div>}
                                {estimation.details.accessories_malus && <div>• Accessoires incomplets : <strong>-10%</strong></div>}
                            </div>
                        </div>
                    )}

                    <div style={{
                        background: '#FFF3CD',
                        border: '1px solid #FFE69C',
                        padding: '1rem',
                        borderRadius: '8px',
                        marginTop: '1.5rem',
                        fontSize: '0.9rem',
                        color: '#856404'
                    }}>
                        ⚠️ <strong>Prix définitif après validation sur place</strong>
                        <br />
                        Notre expert confirmera l'état lors de l'enlèvement.
                    </div>
                </div>

                <div style={{ fontSize: '1rem', color: '#6C7783', marginBottom: '1rem' }}>
                    ✅ Votre demande a été envoyée avec succès !
                </div>
                <p>📧 Vous recevrez une confirmation par email</p>
                <p>📞 Nous vous contactons sous 24h pour validation</p>
                <p>🚚 Enlèvement gratuit à la date de votre choix</p>

                <button
                    onClick={() => window.location.href = '/'}
                    className="btn-primary"
                >
                    Retour à l'accueil maintenant
                </button>

                <div style={{ marginTop: '1.5rem', fontSize: '0.9rem', color: '#6C7783' }}>
                    <i className="bi bi-telephone"></i> Besoin d'aide ?
                    <br />Appelez-nous au <strong>{phone}</strong>
                </div>
            </div>
        );
    }

    return (
        <div className="buyback-form">
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
                        <div className="step-label">Détails</div>
                    </div>
                    <div className="progress-line"></div>
                    <div className={`progress-step ${step >= 3 ? 'active' : ''} ${step > 3 ? 'completed' : ''}`}>
                        <div className="step-circle">3</div>
                        <div className="step-label">État</div>
                    </div>
                    <div className="progress-line"></div>
                    <div className={`progress-step ${step >= 4 ? 'active' : ''} ${step > 4 ? 'completed' : ''}`}>
                        <div className="step-circle">4</div>
                        <div className="step-label">Photos</div>
                    </div>
                    <div className="progress-line"></div>
                    <div className={`progress-step ${step >= 5 ? 'active' : ''}`}>
                        <div className="step-circle">5</div>
                        <div className="step-label">Contact</div>
                    </div>
                </div>
            </div>

            {/* ÉTAPE 1 : CATÉGORIE */}
            {step === 1 && (
                <div className="form-step">
                    <h3 className="h5 mb-4 text-center">Quel type d'appareil souhaitez-vous vendre ?</h3>
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

            {/* ÉTAPE 2 : DÉTAILS */}
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

                    <h3 className="h5 mb-4">Détails de votre {selectedCategory?.name}</h3>

                    <form>
                        <div className="row g-3">
                            <div className="col-12 col-md-6">
                                <label htmlFor="brand" className="form-label">
                                    Marque <span className="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="brand"
                                    name="brand"
                                    value={formData.brand}
                                    onChange={handleInputChange}
                                    placeholder="Ex: Bosch, Samsung, Whirlpool..."
                                    required
                                />
                            </div>

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

                            <div className="col-12">
                                <label htmlFor="purchaseYear" className="form-label">
                                    Année d'achat <span className="text-danger">*</span>
                                </label>
                                <select
                                    className="form-select"
                                    id="purchaseYear"
                                    name="purchaseYear"
                                    value={formData.purchaseYear}
                                    onChange={handleInputChange}
                                    required
                                >
                                    <option value="">Sélectionnez une période</option>
                                    {PURCHASE_YEARS.map((year) => (
                                        <option key={year.value} value={year.value}>
                                            {year.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="col-12">
                                <div className="form-check form-switch">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id="hasInvoice"
                                        name="hasInvoice"
                                        checked={formData.hasInvoice}
                                        onChange={handleInputChange}
                                    />
                                    <label className="form-check-label" htmlFor="hasInvoice">
                                        <strong>J'ai la facture d'achat</strong>
                                        <div className="small text-soft">+10% sur le prix de rachat</div>
                                    </label>
                                </div>
                            </div>

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

            {/* ÉTAPE 3 : ÉTAT */}
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

                    <h3 className="h5 mb-4">État de l'appareil</h3>

                    <form>
                        <div className="row g-4">
                            {/* État fonctionnel */}
                            <div className="col-12">
                                <label className="form-label fw-bold">
                                    🔧 État de fonctionnement <span className="text-danger">*</span>
                                </label>
                                <div className="row g-3">
                                    {FUNCTIONAL_STATES.map((state) => (
                                        <div key={state.value} className="col-6">
                                            <div
                                                className={`state-card ${formData.functionalState === state.value ? 'selected' : ''}`}
                                                onClick={() => setFormData({ ...formData, functionalState: state.value })}
                                                style={{ cursor: 'pointer' }}
                                            >
                                                <div style={{ fontSize: '2rem', marginBottom: '0.5rem' }}>{state.icon}</div>
                                                <div className="fw-bold mb-1">{state.label}</div>
                                                <div className="small text-soft mb-2">{state.description}</div>
                                                <div className="small">
                                                    <span className="badge bg-primary">{state.priceImpact} du prix</span>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* État esthétique */}
                            <div className="col-12">
                                <label className="form-label fw-bold">
                                    🎨 État esthétique <span className="text-danger">*</span>
                                </label>
                                <div className="row g-3">
                                    {AESTHETIC_STATES.map((state) => (
                                        <div key={state.value} className="col-6">
                                            <div
                                                className={`state-card ${formData.aestheticState === state.value ? 'selected' : ''}`}
                                                onClick={() => setFormData({ ...formData, aestheticState: state.value })}
                                                style={{ cursor: 'pointer' }}
                                            >
                                                <div style={{ fontSize: '2rem', marginBottom: '0.5rem' }}>{state.icon}</div>
                                                <div className="fw-bold mb-1">{state.label}</div>
                                                <div className="small text-soft mb-2">{state.description}</div>
                                                <div className="small">
                                                    <span className="badge bg-secondary">{state.priceImpact} du prix</span>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Accessoires */}
                            <div className="col-12">
                                <div className="form-check form-switch">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id="hasAllAccessories"
                                        name="hasAllAccessories"
                                        checked={formData.hasAllAccessories}
                                        onChange={handleInputChange}
                                    />
                                    <label className="form-check-label" htmlFor="hasAllAccessories">
                                        <strong>Tous les accessoires sont présents</strong>
                                        <div className="small text-soft">Manuel, câbles, tuyaux, grilles, etc.</div>
                                    </label>
                                </div>
                            </div>

                            {/* Commentaires */}
                            <div className="col-12">
                                <label htmlFor="additionalComments" className="form-label">
                                    Commentaires additionnels <span className="text-soft">(optionnel)</span>
                                </label>
                                <textarea
                                    className="form-control"
                                    id="additionalComments"
                                    name="additionalComments"
                                    rows="3"
                                    value={formData.additionalComments}
                                    onChange={handleInputChange}
                                    placeholder="Précisions sur l'état, l'historique d'utilisation, etc."
                                ></textarea>
                            </div>

                            <div className="col-12">
                                <button
                                    type="button"
                                    className="btn btn-primary btn-lg w-100"
                                    onClick={goToNextStep}
                                    disabled={!validateStep3()}
                                >
                                    Continuer <i className="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            )}

            {/* ÉTAPE 4 : PHOTOS */}
            {step === 4 && (
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

                    <h3 className="h5 mb-4">Photos de l'appareil</h3>

                    <div className="alert alert-info mb-4">
                        <i className="bi bi-info-circle"></i> <strong>Minimum 2 photos requises</strong>
                        <ul className="mb-0 mt-2 small">
                            <li>Vue d'ensemble de l'appareil</li>
                            <li>Plaque signalétique (marque/modèle)</li>
                            <li>Défauts éventuels (si applicable)</li>
                        </ul>
                    </div>

                    <div className="photo-upload-zone mb-4">
                        <label htmlFor="photoUpload" className="photo-upload-label">
                            <div className="text-center p-5" style={{
                                border: '2px dashed #D7EADF',
                                borderRadius: '12px',
                                background: '#F6F8FA',
                                cursor: 'pointer'
                            }}>
                                <i className="bi bi-camera" style={{ fontSize: '3rem', color: '#16C669' }}></i>
                                <div className="mt-3">
                                    <strong>Cliquez pour ajouter des photos</strong>
                                    <div className="small text-soft mt-1">
                                        ou glissez-déposez vos fichiers
                                    </div>
                                    <div className="small text-soft mt-2">
                                        JPG, PNG, HEIC • Max 5 Mo par photo • Max 5 photos
                                    </div>
                                </div>
                            </div>
                            <input
                                type="file"
                                id="photoUpload"
                                accept="image/*"
                                multiple
                                onChange={handlePhotoUpload}
                                style={{ display: 'none' }}
                            />
                        </label>
                    </div>

                    {/* Aperçu des photos */}
                    {formData.photos.length > 0 && (
                        <div className="row g-3 mb-4">
                            {formData.photos.map((photo, index) => (
                                <div key={index} className="col-4 col-md-3">
                                    <div style={{ position: 'relative' }}>
                                        <img
                                            src={photo}
                                            alt={`Photo ${index + 1}`}
                                            style={{
                                                width: '100%',
                                                height: '120px',
                                                objectFit: 'cover',
                                                borderRadius: '8px'
                                            }}
                                        />
                                        <button
                                            type="button"
                                            onClick={() => removePhoto(index)}
                                            style={{
                                                position: 'absolute',
                                                top: '5px',
                                                right: '5px',
                                                background: 'rgba(255,0,0,0.8)',
                                                color: 'white',
                                                border: 'none',
                                                borderRadius: '50%',
                                                width: '30px',
                                                height: '30px',
                                                cursor: 'pointer',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center'
                                            }}
                                        >
                                            <i className="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    <button
                        type="button"
                        className="btn btn-primary btn-lg w-100"
                        onClick={goToNextStep}
                        disabled={!validateStep4()}
                    >
                        Continuer <i className="bi bi-arrow-right"></i>
                    </button>

                    {formData.photos.length < 2 && (
                        <div className="text-center mt-3 small text-danger">
                            <i className="bi bi-exclamation-triangle"></i> Minimum 2 photos requises ({formData.photos.length}/2)
                        </div>
                    )}
                </div>
            )}

            {/* ÉTAPE 5 : COORDONNÉES */}
            {step === 5 && (
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

                    <h3 className="h5 mb-4">Vos coordonnées & paiement</h3>

                    {submitError && (
                        <div className="alert alert-danger mb-4">
                            <i className="bi bi-exclamation-triangle"></i> {submitError}
                        </div>
                    )}

                    <form onSubmit={handleSubmit}>
                        <div className="row g-3">
                            {/* Prénom & Nom */}
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

                            {/* Email & Téléphone */}
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

                            {/* Adresse */}
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

                            <div className="col-12 col-md-6">
                                <label htmlFor="floor" className="form-label">
                                    Étage <span className="text-soft">(optionnel)</span>
                                </label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="floor"
                                    name="floor"
                                    value={formData.floor}
                                    onChange={handleInputChange}
                                    placeholder="3ème étage"
                                />
                            </div>

                            <div className="col-12 col-md-6">
                                <label className="form-label d-block">&nbsp;</label>
                                <div className="form-check form-switch">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id="hasElevator"
                                        name="hasElevator"
                                        checked={formData.hasElevator}
                                        onChange={handleInputChange}
                                    />
                                    <label className="form-check-label" htmlFor="hasElevator">
                                        Ascenseur disponible
                                    </label>
                                </div>
                            </div>

                            {/* Mode de paiement */}
                            <div className="col-12">
                                <label className="form-label fw-bold">
                                    💳 Mode de paiement souhaité <span className="text-danger">*</span>
                                </label>
                                <div className="row g-3">
                                    <div className="col-6">
                                        <label className={`payment-card ${formData.paymentMethod === 'virement' ? 'selected' : ''}`}>
                                            <input
                                                type="radio"
                                                name="paymentMethod"
                                                value="virement"
                                                checked={formData.paymentMethod === 'virement'}
                                                onChange={handleInputChange}
                                            />
                                            <div className="text-center p-3">
                                                <i className="bi bi-bank" style={{ fontSize: '2rem', color: '#16C669' }}></i>
                                                <div className="fw-bold mt-2">Virement bancaire</div>
                                                <div className="small text-soft">(recommandé)</div>
                                            </div>
                                        </label>
                                    </div>
                                    <div className="col-6">
                                        <label className={`payment-card ${formData.paymentMethod === 'especes' ? 'selected' : ''}`}>
                                            <input
                                                type="radio"
                                                name="paymentMethod"
                                                value="especes"
                                                checked={formData.paymentMethod === 'especes'}
                                                onChange={handleInputChange}
                                            />
                                            <div className="text-center p-3">
                                                <i className="bi bi-cash-stack" style={{ fontSize: '2rem', color: '#16C669' }}></i>
                                                <div className="fw-bold mt-2">Espèces</div>
                                                <div className="small text-soft">Sur place</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {/* Coordonnées bancaires (si virement) */}
                            {formData.paymentMethod === 'virement' && (
                                <>
                                    <div className="col-12">
                                        <div className="alert alert-light">
                                            <i className="bi bi-shield-check"></i> Vos coordonnées bancaires sont sécurisées et utilisées uniquement pour le paiement.
                                        </div>
                                    </div>

                                    <div className="col-12">
                                        <label htmlFor="iban" className="form-label">
                                            IBAN <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            id="iban"
                                            name="iban"
                                            value={formData.iban}
                                            onChange={handleInputChange}
                                            placeholder="FR76 1234 5678 9012 3456 7890 123"
                                            required={formData.paymentMethod === 'virement'}
                                        />
                                    </div>

                                    <div className="col-12">
                                        <label htmlFor="accountHolder" className="form-label">
                                            Titulaire du compte <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            id="accountHolder"
                                            name="accountHolder"
                                            value={formData.accountHolder}
                                            onChange={handleInputChange}
                                            placeholder="Prénom Nom"
                                            required={formData.paymentMethod === 'virement'}
                                        />
                                    </div>
                                </>
                            )}

                            {/* Disponibilités */}
                            <div className="col-12 col-md-6">
                                <label htmlFor="preferredDate" className="form-label">
                                    Date souhaitée pour l'enlèvement <span className="text-soft">(optionnel)</span>
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

                            <div className="col-12 col-md-6">
                                <label className="form-label">
                                    Créneaux horaires préférés <span className="text-soft">(optionnel)</span>
                                </label>
                                <div className="d-flex flex-column gap-2">
                                    <div className="form-check">
                                        <input
                                            className="form-check-input"
                                            type="checkbox"
                                            id="slot-matin"
                                            checked={formData.timeSlots.includes('matin')}
                                            onChange={() => handleTimeSlotChange('matin')}
                                        />
                                        <label className="form-check-label" htmlFor="slot-matin">
                                            Matin (9h-12h)
                                        </label>
                                    </div>
                                    <div className="form-check">
                                        <input
                                            className="form-check-input"
                                            type="checkbox"
                                            id="slot-apres-midi"
                                            checked={formData.timeSlots.includes('apres-midi')}
                                            onChange={() => handleTimeSlotChange('apres-midi')}
                                        />
                                        <label className="form-check-label" htmlFor="slot-apres-midi">
                                            Après-midi (14h-18h)
                                        </label>
                                    </div>
                                    <div className="form-check">
                                        <input
                                            className="form-check-input"
                                            type="checkbox"
                                            id="slot-flexible"
                                            checked={formData.timeSlots.includes('flexible')}
                                            onChange={() => handleTimeSlotChange('flexible')}
                                        />
                                        <label className="form-check-label" htmlFor="slot-flexible">
                                            Flexible
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {/* Soumission */}
                            <div className="col-12">
                                <button
                                    type="submit"
                                    className="btn btn-primary btn-lg w-100"
                                    disabled={!validateStep5() || isSubmitting}
                                >
                                    {isSubmitting ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Envoi en cours...
                                        </>
                                    ) : (
                                        <>
                                            <i className="bi bi-calculator"></i> Obtenir mon estimation
                                        </>
                                    )}
                                </button>
                            </div>

                            <div className="col-12">
                                <div className="alert alert-light">
                                    <small>
                                        <i className="bi bi-shield-check"></i> Vos données sont protégées et utilisées uniquement pour traiter votre demande de rachat.
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
