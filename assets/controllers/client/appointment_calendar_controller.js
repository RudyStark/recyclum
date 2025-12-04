import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        blockedSlots: String,
        token: String
    };

    connect() {
        console.log('Appointment calendar controller connected');

        // Parse les créneaux bloqués
        this.blockedSlots = JSON.parse(this.blockedSlotsValue || '[]');

        // Variables d'état
        this.currentDate = new Date();
        this.selectedDate = null;
        this.selectedTime = null;

        // Créneaux disponibles (9h-12h et 14h-18h)
        this.availableSlots = [
            '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
            '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'
        ];

        // Initialise le calendrier
        this.renderCalendar();

        // Event listeners
        document.getElementById('prev-month').addEventListener('click', () => this.previousMonth());
        document.getElementById('next-month').addEventListener('click', () => this.nextMonth());
        document.getElementById('btn-confirm-appointment')?.addEventListener('click', () => this.confirmAppointment());
    }

    renderCalendar() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();

        // Affiche le mois et l'année
        const monthNames = [
            'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
            'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
        ];
        document.getElementById('current-month-year').textContent = `${monthNames[month]} ${year}`;

        // Premier jour du mois (0 = dimanche, 1 = lundi, etc.)
        const firstDay = new Date(year, month, 1).getDay();
        // Dernier jour du mois
        const lastDate = new Date(year, month + 1, 0).getDate();
        // Dernier jour du mois précédent
        const prevLastDate = new Date(year, month, 0).getDate();

        const calendarGrid = document.getElementById('calendar');
        calendarGrid.innerHTML = '';

        // Ajuste pour que la semaine commence le lundi (0 = lundi)
        const adjustedFirstDay = firstDay === 0 ? 6 : firstDay - 1;

        // Jours du mois précédent
        for (let i = adjustedFirstDay; i > 0; i--) {
            const dayDiv = this.createDayElement(prevLastDate - i + 1, true, false);
            calendarGrid.appendChild(dayDiv);
        }

        // Jours du mois actuel
        const today = new Date();
        for (let day = 1; day <= lastDate; day++) {
            const currentDateObj = new Date(year, month, day);
            const isToday = this.isSameDay(currentDateObj, today);
            const isPast = currentDateObj < today && !isToday;
            const isWeekend = this.isWeekend(currentDateObj);

            const dayDiv = this.createDayElement(day, false, isPast || isWeekend, currentDateObj, isToday);
            calendarGrid.appendChild(dayDiv);
        }

        // Jours du mois suivant pour compléter la grille
        const remainingDays = 42 - (adjustedFirstDay + lastDate); // Grille de 6 semaines
        for (let day = 1; day <= remainingDays; day++) {
            const dayDiv = this.createDayElement(day, true, false);
            calendarGrid.appendChild(dayDiv);
        }

        // Désactive le bouton "mois précédent" si on est au mois actuel
        const isPreviousDisabled = year === today.getFullYear() && month === today.getMonth();
        document.getElementById('prev-month').disabled = isPreviousDisabled;
    }

    createDayElement(dayNumber, isOtherMonth, isDisabled, dateObj = null, isToday = false) {
        const dayDiv = document.createElement('div');
        dayDiv.className = 'calendar-day';

        if (isOtherMonth) {
            dayDiv.classList.add('other-month');
        }

        if (isDisabled) {
            dayDiv.classList.add('disabled');
        }

        if (dateObj && this.isWeekend(dateObj)) {
            dayDiv.classList.add('weekend');
        }

        const dayNumber_el = document.createElement('div');
        dayNumber_el.className = 'calendar-day-number';
        dayNumber_el.textContent = dayNumber;
        dayDiv.appendChild(dayNumber_el);

        // Si c'est un jour sélectionnable
        if (!isOtherMonth && !isDisabled && dateObj) {
            dayDiv.addEventListener('click', () => this.selectDate(dateObj, dayDiv));
        }

        return dayDiv;
    }

    selectDate(dateObj, dayElement) {
        // Retire la sélection précédente
        document.querySelectorAll('.calendar-day.selected').forEach(el => {
            el.classList.remove('selected');
        });

        // Ajoute la nouvelle sélection
        dayElement.classList.add('selected');
        this.selectedDate = dateObj;
        this.selectedTime = null; // Reset le créneau horaire

        // Affiche les créneaux horaires
        this.renderTimeSlots();

        // Affiche la section des créneaux
        document.getElementById('time-slots-section').style.display = 'block';

        // Cache la section de confirmation
        document.getElementById('confirmation-section').style.display = 'none';

        // Scroll vers les créneaux
        document.getElementById('time-slots-section').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    renderTimeSlots() {
        const timeSlotsContainer = document.getElementById('time-slots');
        timeSlotsContainer.innerHTML = '';

        const dateString = this.selectedDate.toISOString().split('T')[0]; // Format YYYY-MM-DD

        this.availableSlots.forEach(time => {
            const timeSlot = document.createElement('div');
            timeSlot.className = 'time-slot';
            timeSlot.textContent = time;

            // Vérifie si le créneau est bloqué
            const isBlocked = this.blockedSlots.some(slot =>
                slot.date === dateString && slot.time === time
            );

            if (isBlocked) {
                timeSlot.classList.add('disabled');
            } else {
                timeSlot.addEventListener('click', () => this.selectTime(time, timeSlot));
            }

            timeSlotsContainer.appendChild(timeSlot);
        });
    }

    selectTime(time, timeElement) {
        // Retire la sélection précédente
        document.querySelectorAll('.time-slot.selected').forEach(el => {
            el.classList.remove('selected');
        });

        // Ajoute la nouvelle sélection
        timeElement.classList.add('selected');
        this.selectedTime = time;

        // Affiche la section de confirmation
        this.showConfirmation();
    }

    showConfirmation() {
        const confirmationSection = document.getElementById('confirmation-section');
        confirmationSection.style.display = 'block';

        // Formate la date en français
        const dateFormatted = this.formatDateFrench(this.selectedDate);

        document.getElementById('selected-date-display').textContent = dateFormatted;
        document.getElementById('selected-time-display').textContent = this.selectedTime;

        // Scroll vers la confirmation
        confirmationSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async confirmAppointment() {
        if (!this.selectedDate || !this.selectedTime) {
            alert('Veuillez sélectionner une date et un créneau horaire');
            return;
        }

        const confirmBtn = document.getElementById('btn-confirm-appointment');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Confirmation en cours...';

        const dateString = this.selectedDate.toISOString().split('T')[0];

        try {
            const response = await fetch(`/rdv/confirmation/${this.tokenValue}/submit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    date: dateString,
                    time: this.selectedTime
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Erreur lors de la confirmation');
            }

            // Succès !
            this.showSuccessMessage(data);

        } catch (error) {
            console.error('Error:', error);
            alert(error.message || 'Une erreur est survenue lors de la confirmation');

            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fa fa-check"></i> Confirmer mon rendez-vous';
        }
    }

    showSuccessMessage(data) {
        const container = document.querySelector('.client-card-body');
        container.innerHTML = `
            <div style="text-align: center; padding: 60px 40px;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #16C669 0%, #12a356 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; box-shadow: 0 8px 24px rgba(22, 198, 105, 0.4);">
                    <i class="fa fa-check" style="font-size: 2.5rem; color: white;"></i>
                </div>

                <h2 style="font-size: 2rem; font-weight: 900; color: #1a2332; margin-bottom: 15px;">
                    ✅ Rendez-vous confirmé !
                </h2>

                <p style="font-size: 1.125rem; color: #6c7783; margin-bottom: 40px;">
                    Votre rendez-vous a été confirmé avec succès
                </p>

                <div style="background: linear-gradient(135deg, rgba(22, 198, 105, 0.08) 0%, rgba(22, 198, 105, 0.02) 100%); border: 2px solid rgba(22, 198, 105, 0.3); border-radius: 12px; padding: 30px; margin-bottom: 30px; text-align: left;">
                    <h3 style="font-weight: 800; color: #1a2332; margin-bottom: 20px; font-size: 1.25rem;">
                        📋 Détails de votre rendez-vous
                    </h3>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px; font-size: 1rem;">
                        <i class="fa fa-calendar" style="color: #16C669; width: 24px;"></i>
                        <span><strong>Date :</strong> ${data.appointment.date}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px; font-size: 1rem;">
                        <i class="fa fa-clock" style="color: #16C669; width: 24px;"></i>
                        <span><strong>Heure :</strong> ${data.appointment.time}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; font-size: 1rem;">
                        <i class="fa fa-wrench" style="color: #16C669; width: 24px;"></i>
                        <span><strong>Type :</strong> Réparation à domicile</span>
                    </div>
                </div>

                <div style="background: #fff9e6; border: 2px solid #f59e0b; border-radius: 12px; padding: 20px; text-align: left; display: flex; gap: 15px; margin-bottom: 30px;">
                    <i class="fa fa-envelope" style="color: #f59e0b; font-size: 1.5rem; flex-shrink: 0;"></i>
                    <p style="margin: 0; color: #92400e; line-height: 1.6;">
                        <strong>Email de confirmation envoyé !</strong><br>
                        Vous allez recevoir un email récapitulatif avec tous les détails de votre rendez-vous.
                    </p>
                </div>

                <p style="color: #6c7783; font-size: 0.95rem; line-height: 1.6;">
                    Notre technicien vous contactera 24h avant l'intervention pour confirmer sa venue.<br>
                    À très bientôt ! 🔧
                </p>
            </div>
        `;
    }

    previousMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() - 1);
        this.renderCalendar();
    }

    nextMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() + 1);
        this.renderCalendar();
    }

    isSameDay(date1, date2) {
        return date1.getFullYear() === date2.getFullYear() &&
            date1.getMonth() === date2.getMonth() &&
            date1.getDate() === date2.getDate();
    }

    isWeekend(date) {
        const day = date.getDay();
        return day === 0 || day === 6; // 0 = dimanche, 6 = samedi
    }

    formatDateFrench(date) {
        const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        const months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        const dayName = days[date.getDay()];
        const day = date.getDate();
        const month = months[date.getMonth()];
        const year = date.getFullYear();

        return `${dayName} ${day} ${month} ${year}`;
    }
}
