document.querySelectorAll('form[data-auto-filter]').forEach((form) => {
    let searchTimer;
    let composing = false;

    const submit = () => {
        clearTimeout(searchTimer);

        const dateFrom = form.elements.namedItem('date_from');
        const dateTo = form.elements.namedItem('date_to');
        if (dateFrom?.value && dateTo?.value && dateTo.value < dateFrom.value) {
            dateTo.value = '';
        }

        form.setAttribute('aria-busy', 'true');
        form.requestSubmit();
    };

    form.querySelectorAll('select, input[type="date"], input[type="month"], input[type="checkbox"]').forEach((control) => {
        control.addEventListener('change', () => {
            submit();
        });
    });

    const dateFrom = form.elements.namedItem('date_from');
    const dateTo = form.elements.namedItem('date_to');
    if (dateFrom && dateTo) {
        dateTo.min = dateFrom.value;
        dateFrom.addEventListener('change', () => {
            dateTo.min = dateFrom.value;
        });
    }

    form.querySelectorAll('input[type="text"][name="search"], input[type="search"]').forEach((input) => {
        input.addEventListener('compositionstart', () => {
            composing = true;
            clearTimeout(searchTimer);
        });
        input.addEventListener('compositionend', () => {
            composing = false;
            searchTimer = setTimeout(submit, 500);
        });
        input.addEventListener('input', () => {
            if (composing) return;
            clearTimeout(searchTimer);
            searchTimer = setTimeout(submit, 500);
        });
        input.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' || composing) return;
            event.preventDefault();
            submit();
        });
    });
});

document.querySelectorAll('form[data-submit-once]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }

        form.dataset.submitting = 'true';
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
        });
    });
});

const balanceTooltipButtons = document.querySelectorAll('[data-balance-tooltip]');
if (balanceTooltipButtons.length > 0) {
    const tooltip = document.createElement('div');
    tooltip.className = 'pointer-events-none fixed z-[200] hidden w-44 rounded-lg bg-slate-900 px-3 py-2 text-left text-[11px] font-normal leading-relaxed text-white shadow-lg';
    tooltip.setAttribute('role', 'tooltip');
    document.body.appendChild(tooltip);

    let activeButton = null;
    let pinnedButton = null;

    const positionTooltip = (button) => {
        const rect = button.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
        left = Math.max(8, Math.min(left, window.innerWidth - tooltipRect.width - 8));

        let top = rect.bottom + 8;
        if (top + tooltipRect.height > window.innerHeight - 8) {
            top = rect.top - tooltipRect.height - 8;
        }

        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${Math.max(8, top)}px`;
    };

    const showTooltip = (button) => {
        const content = button.parentElement.querySelector('.balance-tooltip-content');
        if (!content) return;

        if (activeButton && activeButton !== button) {
            activeButton.setAttribute('aria-expanded', 'false');
        }
        activeButton = button;
        tooltip.innerHTML = content.innerHTML;
        tooltip.classList.remove('hidden');
        button.setAttribute('aria-expanded', 'true');
        positionTooltip(button);
    };

    const hideTooltip = () => {
        if (activeButton) activeButton.setAttribute('aria-expanded', 'false');
        tooltip.classList.add('hidden');
        activeButton = null;
        pinnedButton = null;
    };

    balanceTooltipButtons.forEach((button) => {
        button.addEventListener('mouseenter', () => showTooltip(button));
        button.addEventListener('mouseleave', () => {
            if (pinnedButton !== button) hideTooltip();
        });
        button.addEventListener('focus', () => showTooltip(button));
        button.addEventListener('blur', () => {
            if (pinnedButton !== button) hideTooltip();
        });
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            if (pinnedButton === button) {
                hideTooltip();
                return;
            }
            pinnedButton = button;
            showTooltip(button);
        });
    });

    document.addEventListener('click', hideTooltip);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') hideTooltip();
    });
    window.addEventListener('scroll', hideTooltip, true);
    window.addEventListener('resize', hideTooltip);
}
