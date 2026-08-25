export function registerCalendarComponents() {
    Alpine.data('scheduleManager', () => ({
        panelOpen: false,
        modalOpen: false,
        mode: 'create',
        scheduleId: null,
        name: '',
        days: ['1', '2', '3', '4', '5'],
        startTime: '09:00',
        endTime: '17:00',
        color: '#14b8a6',
        validFrom: '',
        validUntil: '',
        active: true,

        get formAction() {
            return this.mode === 'edit'
                ? `/calendar/schedules/${this.scheduleId}`
                : '/calendar/schedules';
        },

        openCreate() {
            this.reset();
            this.modalOpen = true;
        },

        openEdit(data) {
            this.scheduleId  = data.id;
            this.name        = data.name;
            this.days        = (data.days || []).map(String);
            this.startTime   = data.start_time;
            this.endTime     = data.end_time;
            this.color       = data.color || '#14b8a6';
            this.validFrom   = data.valid_from || '';
            this.validUntil  = data.valid_until || '';
            this.active      = data.active;
            this.mode        = 'edit';
            this.modalOpen   = true;
        },

        reset() {
            this.scheduleId = null;
            this.name       = '';
            this.days       = ['1', '2', '3', '4', '5'];
            this.startTime  = '09:00';
            this.endTime    = '17:00';
            this.color      = '#14b8a6';
            this.validFrom  = '';
            this.validUntil = '';
            this.active     = true;
            this.mode       = 'create';
        },

        confirmDelete() {
            if (confirm('Remove this work schedule?')) {
                this.$refs.deleteScheduleForm.submit();
            }
        },
    }));

    Alpine.data('calendarModal', () => ({
        open: false,
        mode: 'create',
        eventId: null,
        title: '',
        description: '',
        type: 'event',
        allDay: true,
        startsAtDate: '',
        startsAtTime: '00:00',
        endsAtDate: '',
        endsAtTime: '',
        recurrence: 'none',
        recurrenceEndsAt: '',

        openCreate(date) {
            this.reset();
            this.startsAtDate = date;
            this.mode = 'create';
            this.open = true;
        },

        openEdit(data) {
            this.eventId         = data.id;
            this.title           = data.title;
            this.description     = data.description || '';
            this.type            = data.type;
            this.allDay          = data.all_day;
            this.startsAtDate    = data.starts_at_date;
            this.startsAtTime    = data.starts_at_time || '00:00';
            this.endsAtDate      = data.ends_at_date || '';
            this.endsAtTime      = data.ends_at_time || '';
            this.recurrence      = data.recurrence;
            this.recurrenceEndsAt = data.recurrence_ends_at || '';
            this.mode = 'edit';
            this.open = true;
        },

        reset() {
            this.eventId          = null;
            this.title            = '';
            this.description      = '';
            this.type             = 'event';
            this.allDay           = true;
            this.startsAtDate     = '';
            this.startsAtTime     = '00:00';
            this.endsAtDate       = '';
            this.endsAtTime       = '';
            this.recurrence       = 'none';
            this.recurrenceEndsAt = '';
        },

        submitForm() {
            const form   = this.$refs.eventForm;
            form.action  = this.mode === 'edit'
                ? `/calendar/${this.eventId}`
                : '/calendar';

            const methodInput = form.querySelector('input[name="_method"]');
            if (methodInput) {
                methodInput.value = this.mode === 'edit' ? 'PATCH' : '';
            }

            form.submit();
        },

        confirmDelete() {
            if (confirm('Remove this event?')) {
                this.$refs.deleteForm.submit();
            }
        },
    }));
}
