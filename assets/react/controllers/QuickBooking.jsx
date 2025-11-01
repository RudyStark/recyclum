import React, { useState } from 'react';

export default function QuickBooking({ defaultZip = '75012', phone = '01 43 07 63 63' }) {
    const [form, setForm] = useState({ name: '', phone: '', zip: defaultZip, service: 'reparation' });
    const [submitted, setSubmitted] = useState(false);

    const onChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

    const onSubmit = (e) => {
        e.preventDefault();
        // TODO: brancher API backend + validation + captcha
        setSubmitted(true);
    };

    if (submitted) {
        return (
            <div className="alert alert-success mb-0" role="alert" aria-live="polite">
                Merci ! Nous vous rappelons rapidement pour confirmer le créneau.
            </div>
        );
    }

    return (
        <form className="row gy-2 gx-2 align-items-end" onSubmit={onSubmit} noValidate>
            <div className="col-12 col-md-4">
                <label htmlFor="qb-name" className="form-label">Nom</label>
                <input id="qb-name" name="name" className="form-control" required
                       value={form.name} onChange={onChange} placeholder="Votre nom" />
            </div>
            <div className="col-12 col-md-4">
                <label htmlFor="qb-phone" className="form-label">Téléphone</label>
                <input id="qb-phone" name="phone" className="form-control" required
                       value={form.phone} onChange={onChange} placeholder="06 xx xx xx xx" />
            </div>
            <div className="col-6 col-md-2">
                <label htmlFor="qb-zip" className="form-label">Code postal</label>
                <input id="qb-zip" name="zip" className="form-control" required
                       value={form.zip} onChange={onChange} />
            </div>
            <div className="col-6 col-md-2">
                <label htmlFor="qb-service" className="form-label">Service</label>
                <select id="qb-service" name="service" className="form-select" value={form.service} onChange={onChange}>
                    <option value="reparation">Réparation</option>
                    <option value="rachat">Rachat</option>
                    <option value="express">Express</option>
                </select>
            </div>
            <div className="col-12 d-grid d-md-block mt-2">
                <button className="btn btn-primary btn-lg" type="submit" aria-label="Demander un rappel">
                    Demander un rappel
                </button>
                <span className="d-block d-md-inline ms-md-3 mt-2 mt-md-0 text-body-secondary small">
          ou appelez le <a href={`tel:${phone.replace(/\s+/g,'')}`} className="link-body-emphasis fw-semibold">{phone}</a>
        </span>
            </div>
        </form>
    );
}
