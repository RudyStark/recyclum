import React, { useState, useEffect } from 'react';

// ============================================
// CATÉGORIES D'APPAREILS
// ============================================
// ============================================
// CATÉGORIES D'APPAREILS - ICÔNES PRO
// ============================================
const CATEGORIES = [
    {
        id: 'lave-linge',
        name: 'Lave-linge',
        icon: 'bi-tsunami', // Vagues pour eau
        description: 'Machine à laver'
    },
    {
        id: 'refrigerateur',
        name: 'Réfrigérateur',
        icon: 'bi-snow2', // Flocon pour froid
        description: 'Frigo, congélateur, combi'
    },
    {
        id: 'four',
        name: 'Four',
        icon: 'bi-fire', // Flamme
        description: 'Four encastrable, cuisinière'
    },
    {
        id: 'lave-vaisselle',
        name: 'Lave-vaisselle',
        icon: 'bi-droplet-half', // Goutte pour eau
        description: 'Tous types de lave-vaisselle'
    },
    {
        id: 'seche-linge',
        name: 'Sèche-linge',
        icon: 'bi-wind', // Vent pour séchage
        description: 'Condensation, évacuation'
    },
    {
        id: 'cuisiniere',
        name: 'Cuisinière',
        icon: 'bi-grid-3x3-gap', // Grille pour plaques
        description: 'Cuisinière, piano de cuisson'
    },
    {
        id: 'micro-ondes',
        name: 'Micro-ondes',
        icon: 'bi-lightning-charge', // Éclair pour micro-ondes
        description: 'Micro-ondes classique ou grill'
    },
    {
        id: 'cave-a-vin',
        name: 'Cave à vin',
        icon: 'bi-heart-pulse', // Température contrôlée
        description: 'Cave de service ou vieillissement'
    },
    {
        id: 'hotte',
        name: 'Hotte',
        icon: 'bi-fan', // Ventilateur
        description: 'Hotte aspirante, décor'
    },
    {
        id: 'petit-electromenager',
        name: 'Petit électroménager',
        icon: 'bi-cup-hot', // Tasse chaude
        description: 'Robot, cafetière, bouilloire'
    }
];

// ============================================
// MARQUES DISPONIBLES
// ============================================
const BRANDS = [
    { value: 'miele', label: 'Miele' },
    { value: 'bosch', label: 'Bosch' },
    { value: 'siemens', label: 'Siemens' },
    { value: 'samsung', label: 'Samsung' },
    { value: 'lg', label: 'LG' },
    { value: 'whirlpool', label: 'Whirlpool' },
    { value: 'electrolux', label: 'Electrolux' },
    { value: 'aeg', label: 'AEG' },
    { value: 'liebherr', label: 'Liebherr' },
    { value: 'beko', label: 'Beko' },
    { value: 'candy', label: 'Candy' },
    { value: 'indesit', label: 'Indesit' },
    { value: 'hotpoint', label: 'Hotpoint' },
    { value: 'haier', label: 'Haier' },
    { value: 'smeg', label: 'Smeg' },
    { value: 'brandt', label: 'Brandt' }
];

// ============================================
// ÉTATS FONCTIONNELS - ICÔNES PRO
// ============================================
const FUNCTIONAL_STATES = [
    {
        value: 'perfect',
        label: 'Fonctionne parfaitement',
        icon: 'bi-check-circle-fill',
        iconColor: '#16C669',
        description: 'Aucun défaut de fonctionnement',
        priceImpact: '100%'
    },
    {
        value: 'working',
        label: 'Fonctionne bien',
        icon: 'bi-check-circle',
        iconColor: '#16C669',
        description: 'Quelques signes d\'usage',
        priceImpact: '85%'
    },
    {
        value: 'minor_issues',
        label: 'Petits problèmes',
        icon: 'bi-exclamation-triangle',
        iconColor: '#FFA500',
        description: 'Défauts mineurs réparables',
        priceImpact: '60%'
    },
    {
        value: 'major_issues',
        label: 'Gros problèmes',
        icon: 'bi-exclamation-triangle-fill',
        iconColor: '#FF6B6B',
        description: 'Défauts importants',
        priceImpact: '30%'
    },
    {
        value: 'not_working',
        label: 'Ne fonctionne pas',
        icon: 'bi-x-circle-fill',
        iconColor: '#DC3545',
        description: 'Hors service',
        priceImpact: '10%'
    }
];

// ============================================
// ÉTATS ESTHÉTIQUES - ICÔNES PRO
// ============================================
const AESTHETIC_STATES = [
    {
        value: 'excellent',
        label: 'Comme neuf',
        icon: 'bi-star-fill',
        iconColor: '#FFD700',
        description: 'État impeccable',
        priceImpact: '100%'
    },
    {
        value: 'good',
        label: 'Bon état',
        icon: 'bi-star-half',
        iconColor: '#16C669',
        description: 'Traces d\'usage légères',
        priceImpact: '85%'
    },
    {
        value: 'fair',
        label: 'État correct',
        icon: 'bi-star',
        iconColor: '#3498DB',
        description: 'Quelques rayures',
        priceImpact: '70%'
    },
    {
        value: 'poor',
        label: 'Usagé',
        icon: 'bi-dash-circle',
        iconColor: '#95A5A6',
        description: 'Rayures, bosses visibles',
        priceImpact: '50%'
    },
    {
        value: 'very_poor',
        label: 'Très usagé',
        icon: 'bi-dash-circle-fill',
        iconColor: '#7F8C8D',
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
        serialNumber: '',
        purchaseYear: '',
        hasInvoice: false,
        functionalCondition: '',
        aestheticCondition: '',
        hasAllAccessories: true,
        defectsDescription: '',
        photo1: null,
        photo2: null,
        photo3: null,
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        address: '',
        postalCode: '',
        city: '',
        paymentMethod: 'virement',
        iban: ''
    });
    const [photos, setPhotos] = useState([]);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submitSuccess, setSubmitSuccess] = useState(false);
    const [submitError, setSubmitError] = useState(null);
    const [estimatedPrice, setEstimatedPrice] = useState(null);

    // Autocomplete modèles
    const [modelSuggestions, setModelSuggestions] = useState([]);
    const [isSearching, setIsSearching] = useState(false);
    const [showSuggestions, setShowSuggestions] = useState(false);

    // Handlers
    const handleCategorySelect = (category) => {
        setSelectedCategory(category);
        setFormData({ ...formData, category: category.id });
        setStep(2);
    };

    const validateIBAN = (iban) => {
        const cleanIBAN = iban.replace(/\s/g, '');
        const ibanRegex = /^FR[0-9]{25}$/;
        return ibanRegex.test(cleanIBAN);
    };

    const handleInputChange = (e) => {
        const { name, value, type, checked } = e.target;
        let newValue = type === 'checkbox' ? checked : value;

        // Formatage IBAN
        if (name === 'iban') {
            newValue = value.replace(/\s/g, '').toUpperCase().match(/.{1,4}/g)?.join(' ') || value;
        }

        setFormData({
            ...formData,
            [name]: newValue
        });

        // Recherche modèles si changement de marque ou modèle
        if (name === 'brand' || name === 'model') {
            searchModels(name === 'model' ? value : formData.model, name === 'brand' ? value : formData.brand);
        }
    };

    // Recherche de modèles
    const searchModels = async (query, brand) => {
        if (!query || query.length < 2) {
            setModelSuggestions([]);
            return;
        }

        setIsSearching(true);
        try {
            const params = new URLSearchParams({
                q: query,
                category: formData.category
            });
            if (brand) params.append('brand', brand);

            const response = await fetch(`/api/buyback/search-models?${params}`);
            const data = await response.json();

            if (data.success) {
                setModelSuggestions(data.results);
                setShowSuggestions(true);
            }
        } catch (error) {
            console.error('Erreur recherche modèles:', error);
        } finally {
            setIsSearching(false);
        }
    };

    const selectModel = (model) => {
        setFormData({
            ...formData,
            brand: model.brand || formData.brand,
            model: model.reference || model.fullName // Utilise la référence comme valeur
        });
        setModelSuggestions([]);
        setShowSuggestions(false);
    };

    const handlePhotoUpload = async (e) => {
        const files = Array.from(e.target.files);

        if (photos.length + files.length > 3) {
            alert('Maximum 3 photos autorisées');
            return;
        }

        const photoPromises = files.map(file => {
            return new Promise((resolve, reject) => {
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
            const newPhotos = await Promise.all(photoPromises);
            const updatedPhotos = [...photos, ...newPhotos];
            setPhotos(updatedPhotos);

            // Mettre à jour formData
            const photoData = {};
            updatedPhotos.forEach((photo, index) => {
                photoData[`photo${index + 1}`] = photo;
            });
            setFormData({ ...formData, ...photoData });
        } catch (error) {
            console.error('Erreur upload photos:', error);
        }
    };

    const removePhoto = (index) => {
        const updatedPhotos = photos.filter((_, i) => i !== index);
        setPhotos(updatedPhotos);

        // Réorganiser les photos dans formData
        const photoData = { photo1: null, photo2: null, photo3: null };
        updatedPhotos.forEach((photo, i) => {
            photoData[`photo${i + 1}`] = photo;
        });
        setFormData({ ...formData, ...photoData });
    };

    // Validations
    const validateStep2 = () => {
        return formData.brand.trim() && formData.model.trim() && formData.purchaseYear;
    };

    const validateStep3 = () => {
        return formData.functionalCondition && formData.aestheticCondition;
    };

    const validateStep4 = () => {
        return photos.length >= 2;
    };

    const validateStep5 = () => {
        const basicValid = formData.firstName.trim()
            && formData.lastName.trim()
            && formData.email.trim()
            && formData.phone.trim()
            && formData.address.trim()
            && formData.postalCode.trim()
            && formData.city.trim();

        if (!basicValid) return false;

        if (formData.paymentMethod === 'virement') {
            return validateIBAN(formData.iban);
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
                throw new Error(data.message || 'Erreur lors de l\'envoi');
            }

            setEstimatedPrice(data.estimated_price);
            setSubmitSuccess(true);

        } catch (error) {
            console.error('Erreur:', error);
            setSubmitError(error.message || 'Une erreur est survenue. Veuillez réessayer.');
        } finally {
            setIsSubmitting(false);
        }
    };

    // Affichage succès
    if (submitSuccess && estimatedPrice) {
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
                        💰 Prix estimé
                    </div>
                    <div style={{ fontSize: '2.5rem', fontWeight: '900', color: '#16C669', marginBottom: '1.5rem' }}>
                        {estimatedPrice} €
                    </div>

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
                <p>🚚 Enlèvement gratuit à domicile</p>

                <button
                    onClick={() => window.location.href = '/'}
                    className="btn-primary"
                    style={{
                        marginTop: '2rem',
                        padding: '1rem 2rem',
                        fontSize: '1.1rem',
                        background: '#16C669',
                        color: 'white',
                        border: 'none',
                        borderRadius: '8px',
                        cursor: 'pointer'
                    }}
                >
                    Retour à l'accueil
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
                            <div className="col-12">
                                <label htmlFor="brand" className="form-label">
                                    Marque <span className="text-danger">*</span>
                                </label>
                                <select
                                    className="form-select"
                                    id="brand"
                                    name="brand"
                                    value={formData.brand}
                                    onChange={handleInputChange}
                                    required
                                >
                                    <option value="">Sélectionnez une marque</option>
                                    {BRANDS.map((brand) => (
                                        <option key={brand.value} value={brand.value}>
                                            {brand.label}
                                        </option>
                                    ))}
                                </select>
                                <div className="form-text">
                                    <i className="bi bi-info-circle"></i> Sélectionnez la marque pour activer l'autocomplete des modèles
                                </div>
                            </div>

                            <div className="col-12">
                                <label htmlFor="model" className="form-label">
                                    Modèle ou référence <span className="text-danger">*</span>
                                </label>
                                <div style={{ position: 'relative' }}>
                                    <input
                                        type="text"
                                        className="form-control"
                                        id="model"
                                        name="model"
                                        value={formData.model}
                                        onChange={handleInputChange}
                                        onFocus={() => setShowSuggestions(true)}
                                        onBlur={() => setTimeout(() => setShowSuggestions(false), 200)}
                                        placeholder={formData.brand ? "Ex: WAW28750FF, RF65DG9H0ESR..." : "Sélectionnez d'abord une marque"}
                                        required
                                        disabled={!formData.brand}
                                    />
                                    {isSearching && (
                                        <div style={{ position: 'absolute', right: '10px', top: '10px' }}>
                                            <span className="spinner-border spinner-border-sm" role="status"></span>
                                        </div>
                                    )}
                                    {showSuggestions && modelSuggestions.length > 0 && (
                                        <div className="model-suggestions">
                                            {modelSuggestions.map((model, index) => (
                                                <div
                                                    key={index}
                                                    className="suggestion-item"
                                                    onClick={() => selectModel(model)}
                                                >
                                                    <div className="suggestion-reference">
                                                        {model.reference}
                                                    </div>
                                                    <div className="suggestion-details">
                                                        {model.name} • {model.year}
                                                        {model.tierLabel && (
                                                            <span className="ms-2">
                                                    <span className="badge bg-primary" style={{ fontSize: '0.75rem' }}>
                                                        {model.tierLabel}
                                                    </span>
                                                </span>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                                <div className="form-text">
                                    <i className="bi bi-info-circle"></i> <strong>Obligatoire pour une estimation précise.</strong> Commencez à saisir pour voir les suggestions.
                                </div>
                                {!formData.brand && (
                                    <div className="alert alert-warning mt-2" style={{ fontSize: '0.9rem', padding: '0.75rem' }}>
                                        <i className="bi bi-exclamation-triangle"></i> Veuillez d'abord sélectionner une marque pour activer la recherche de modèles.
                                    </div>
                                )}
                                {formData.brand && (
                                    <div className="alert alert-info mt-2" style={{ fontSize: '0.9rem', padding: '0.75rem' }}>
                                        <i className="bi bi-search"></i> <strong>Comment trouver la référence ?</strong>
                                        <ul className="mb-0 mt-1" style={{ fontSize: '0.85rem' }}>
                                            <li>Sur la plaque signalétique de l'appareil</li>
                                            <li>À l'intérieur de la porte (lave-linge, lave-vaisselle)</li>
                                            <li>À l'arrière ou sous l'appareil</li>
                                            <li>Sur la facture d'achat</li>
                                        </ul>
                                    </div>
                                )}
                            </div>

                            <div className="col-12">
                                <label htmlFor="serialNumber" className="form-label">
                                    Numéro de série <span className="text-soft">(optionnel)</span>
                                </label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="serialNumber"
                                    name="serialNumber"
                                    value={formData.serialNumber}
                                    onChange={handleInputChange}
                                    placeholder="Ex: ABC123456789"
                                />
                                <div className="form-text">
                                    <i className="bi bi-info-circle"></i> Se trouve généralement à l'arrière ou sous l'appareil
                                </div>
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
                                    <option value="">Sélectionnez une année</option>
                                    {Array.from({ length: 16 }, (_, i) => new Date().getFullYear() - i).map(year => (
                                        <option key={year} value={year}>{year}</option>
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
                                        <div className="small text-success">
                                            <i className="bi bi-cash-coin"></i> +10% sur le prix de rachat
                                        </div>
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
                                {(!formData.brand || !formData.model.trim() || !formData.purchaseYear) && (
                                    <div className="text-center mt-2 small text-danger">
                                        <i className="bi bi-exclamation-triangle"></i> Veuillez remplir tous les champs obligatoires (*)
                                    </div>
                                )}
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
                                                className={`state-card ${formData.functionalCondition === state.value ? 'selected' : ''}`}
                                                onClick={() => setFormData({ ...formData, functionalCondition: state.value })}
                                                data-state={state.value}
                                                style={{ cursor: 'pointer' }}
                                            >
                                                <i className={`bi ${state.icon}`} style={{
                                                    fontSize: '2.5rem',
                                                    color: state.iconColor,
                                                    marginBottom: '0.75rem',
                                                    display: 'block'
                                                }}></i>
                                                <div className="fw-bold mb-1" style={{ fontSize: '1rem' }}>{state.label}</div>
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
                                                className={`state-card ${formData.aestheticCondition === state.value ? 'selected' : ''}`}
                                                onClick={() => setFormData({ ...formData, aestheticCondition: state.value })}
                                                style={{ cursor: 'pointer' }}
                                            >
                                                <i className={`bi ${state.icon}`} style={{
                                                    fontSize: '2.5rem',
                                                    color: state.iconColor,
                                                    marginBottom: '0.75rem',
                                                    display: 'block'
                                                }}></i>
                                                <div className="fw-bold mb-1" style={{ fontSize: '1rem' }}>{state.label}</div>
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

                            {/* Description défauts */}
                            <div className="col-12">
                                <label htmlFor="defectsDescription" className="form-label">
                                    Description des défauts <span className="text-soft">(optionnel)</span>
                                </label>
                                <textarea
                                    className="form-control"
                                    id="defectsDescription"
                                    name="defectsDescription"
                                    rows="3"
                                    value={formData.defectsDescription}
                                    onChange={handleInputChange}
                                    placeholder="Précisions sur l'état, défauts visibles, historique..."
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
                                        JPG, PNG, HEIC • Max 5 Mo par photo • Max 3 photos
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
                    {photos.length > 0 && (
                        <div className="row g-3 mb-4">
                            {photos.map((photo, index) => (
                                <div key={index} className="col-4">
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

                    {photos.length < 2 && (
                        <div className="text-center mt-3 small text-danger">
                            <i className="bi bi-exclamation-triangle"></i> Minimum 2 photos requises ({photos.length}/2)
                        </div>
                    )}
                </div>
            )}

            {/* ÉTAPE 5 : COORDONNÉES - Suite dans le prochain message... */}
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
                                <label htmlFor="postalCode" className="form-label">
                                    Code postal <span className="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="postalCode"
                                    name="postalCode"
                                    value={formData.postalCode}
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

                            {/* Mode de paiement */}
                            <div className="col-12">
                                <label className="form-label fw-bold">
                                    💳 Mode de paiement souhaité <span className="text-danger">*</span>
                                </label>
                                <div className="row g-3">
                                    <div className="col-6">
                                        <label className={`payment-card ${formData.paymentMethod === 'virement' ? 'selected' : ''}`}
                                               style={{
                                                   display: 'block',
                                                   border: '2px solid ' + (formData.paymentMethod === 'virement' ? '#16C669' : '#D7EADF'),
                                                   borderRadius: '8px',
                                                   cursor: 'pointer',
                                                   transition: 'all 0.2s'
                                               }}>
                                            <input
                                                type="radio"
                                                name="paymentMethod"
                                                value="virement"
                                                checked={formData.paymentMethod === 'virement'}
                                                onChange={handleInputChange}
                                                style={{ display: 'none' }}
                                            />
                                            <div className="text-center p-3">
                                                <i className="bi bi-bank" style={{ fontSize: '2rem', color: '#16C669' }}></i>
                                                <div className="fw-bold mt-2">Virement bancaire</div>
                                                <div className="small text-soft">(recommandé)</div>
                                            </div>
                                        </label>
                                    </div>
                                    <div className="col-6">
                                        <label className={`payment-card ${formData.paymentMethod === 'especes' ? 'selected' : ''}`}
                                               style={{
                                                   display: 'block',
                                                   border: '2px solid ' + (formData.paymentMethod === 'especes' ? '#16C669' : '#D7EADF'),
                                                   borderRadius: '8px',
                                                   cursor: 'pointer',
                                                   transition: 'all 0.2s'
                                               }}>
                                            <input
                                                type="radio"
                                                name="paymentMethod"
                                                value="especes"
                                                checked={formData.paymentMethod === 'especes'}
                                                onChange={handleInputChange}
                                                style={{ display: 'none' }}
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

                            {/* IBAN si virement */}
                            {formData.paymentMethod === 'virement' && (
                                <>
                                    <div className="col-12">
                                        <div className="alert alert-warning">
                                            <i className="bi bi-exclamation-triangle"></i> <strong>Important</strong>
                                            <br />Le virement sera effectué <strong>après validation de l'état</strong> par notre transporteur.
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
                                            maxLength="34"
                                            required={formData.paymentMethod === 'virement'}
                                        />
                                        <div className="form-text">
                                            <i className="bi bi-info-circle"></i> Format français : FR + 25 chiffres (27 caractères)
                                        </div>
                                        {formData.iban && !validateIBAN(formData.iban) && (
                                            <div className="text-danger small mt-1">
                                                <i className="bi bi-exclamation-triangle"></i> IBAN invalide
                                            </div>
                                        )}
                                    </div>
                                </>
                            )}

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
                                        <i className="bi bi-shield-check"></i> Vos données sont protégées et utilisées uniquement pour traiter votre demande.
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
