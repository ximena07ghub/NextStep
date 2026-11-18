import { sectorOptions } from '../../data/sectorOptions.js'


document.addEventListener('DOMContentLoaded', () => {

    const page = document.querySelector('[data-student-home]')

    if (!page) {
        return
    }


    /*  DATOS DE VACANTES  */

    const vacanciesData = page.querySelector(
        '[data-vacancies-json]'
    )

    const vacancies = vacanciesData
        ? JSON.parse(vacanciesData.textContent)
        : []


    /*  BÚSQUEDA AVANZADA  */

    const searchForm = page.querySelector(
        '[data-vacancy-search]'
    )

    const advancedToggle = page.querySelector(
        '[data-advanced-toggle]'
    )

    const advancedPanel = page.querySelector(
        '[data-advanced-panel]'
    )

    const advancedIcon = page.querySelector(
        '[data-advanced-icon]'
    )


    advancedToggle?.addEventListener('click', () => {

        const isOpen =
            advancedToggle.getAttribute('aria-expanded') === 'true'

        const newState = !isOpen

        advancedToggle.setAttribute(
            'aria-expanded',
            String(newState)
        )

        advancedPanel.hidden = !newState

        searchForm.classList.toggle(
            'vacancy-search--expanded',
            newState
        )

        advancedIcon.classList.toggle(
            'vacancy-search__advanced-icon--open',
            newState
        )

    })


    /*  DROPDOWN DE SECTOR  */

    const sectorTrigger = page.querySelector(
        '[data-sector-trigger]'
    )

    const sectorMenu = page.querySelector(
        '[data-sector-menu]'
    )

    const sectorInput = page.querySelector(
        '[data-sector-input]'
    )

    const sectorLabel = page.querySelector(
        '[data-sector-label]'
    )

    const sectorIcon = page.querySelector(
        '[data-sector-icon]'
    )


    const closeSectorDropdown = () => {

        if (!sectorMenu || !sectorTrigger || !sectorIcon) return
        sectorMenu.hidden = true

        sectorTrigger.classList.remove(
            'vacancy-search__sector-trigger--open'
        )

        sectorIcon.classList.remove(
            'vacancy-search__sector-icon--open'
        )

    }


    const openSectorDropdown = () => {

        if (!sectorMenu || !sectorTrigger || !sectorIcon) return
        sectorMenu.hidden = false

        sectorTrigger.classList.add(
            'vacancy-search__sector-trigger--open'
        )

        sectorIcon.classList.add(
            'vacancy-search__sector-icon--open'
        )

    }


    if (sectorMenu && sectorInput && sectorLabel) sectorOptions.forEach((option) => {

        const button = document.createElement('button')

        button.type = 'button'

        button.className =
            'vacancy-search__sector-option'

        button.textContent = option.label

        button.dataset.value = option.value


        button.addEventListener('click', () => {

            sectorInput.value = option.value
            sectorLabel.textContent = option.label

            sectorLabel.classList.remove(
                'vacancy-search__sector-placeholder'
            )


            sectorMenu
                .querySelectorAll(
                    '.vacancy-search__sector-option'
                )
                .forEach((item) => {

                    item.classList.toggle(
                        'vacancy-search__sector-option--selected',
                        item === button
                    )

                })


            closeSectorDropdown()

        })


        sectorMenu.appendChild(button)

    })


    sectorTrigger?.addEventListener('click', () => {

        if (!sectorMenu) return
        if (sectorMenu.hidden) {
            openSectorDropdown()
            return
        }

        closeSectorDropdown()

    })


    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeSectorDropdown()
    })

    document.addEventListener('click', (event) => {

        const dropdown = page.querySelector(
            '.vacancy-search__sector-dropdown'
        )

        if (!dropdown?.contains(event.target)) {
            closeSectorDropdown()
        }

    })


    /*  FORMULARIO DE BÚSQUEDA  */

    searchForm?.addEventListener('submit', (event) => {

        event.preventDefault()

        const formData = new FormData(searchForm)

        const filters = Object.fromEntries(
            formData.entries()
        )

        const query = String(filters.query || '').toLocaleLowerCase('es-MX').trim()
        const location = String(filters.location || '').toLocaleLowerCase('es-MX').trim()
        const minSalary = Number(filters.min_salary || 0)
        const maxSalary = Number(filters.max_salary || 0)
        const age = String(filters.publication_age || '')
        const sector = String(filters.sector || '')
        let visibleCount = 0
        const feedback = page.querySelector('[data-vacancy-search-feedback]')
        if (minSalary && maxSalary && minSalary > maxSalary) {
            if (feedback) {
                feedback.hidden = false
                feedback.textContent = 'El sueldo mínimo no puede ser mayor al máximo.'
            }
            return
        }
        vacancyCards.forEach(card => {
            const vacancy = vacancies.find(item => String(item.id) === String(card.dataset.vacancyId))
            const publishedAt = vacancy?.createdAt ? Date.parse(vacancy.createdAt) : NaN
            const daysOld = Number.isFinite(publishedAt) ? (Date.now() - publishedAt) / 86400000 : null
            const dateMatches = !age || (daysOld !== null && (age === 'older_21' ? daysOld > 21 : daysOld >= 0 && daysOld <= Number(age)))
            card.hidden = !vacancy || !(
                (!query || `${vacancy.title} ${vacancy.company}`.toLocaleLowerCase('es-MX').includes(query)) &&
                (!location || String(vacancy.location || '').toLocaleLowerCase('es-MX').includes(location)) &&
                (!minSalary || vacancy.salaryFixed >= minSalary) &&
                (!maxSalary || vacancy.salaryFixed <= maxSalary) &&
                (!sector || vacancy.sector === sector) &&
                dateMatches
            )
            if (!card.hidden) visibleCount++
        })
        if (feedback) {
            feedback.hidden = visibleCount > 0
            feedback.textContent = visibleCount ? '' : 'No encontramos vacantes con estos filtros. Prueba otra búsqueda.'
        }
        const firstVisible = Array.from(vacancyCards).find(card => !card.hidden)
        const previewStillVisible = Array.from(vacancyCards).some(card => !card.hidden && card.dataset.vacancyId === preview?.dataset.vacancyId)
        // ocultamos el detalle cuando la búsqueda no tiene resultados
        if (preview) preview.hidden = !firstVisible
        if (firstVisible && !previewStillVisible) {
            const firstVacancy = vacancies.find(item => String(item.id) === String(firstVisible.dataset.vacancyId))
            if (firstVacancy) updatePreview(firstVacancy)
        }

    })


    /*  VISTA PREVIA  */

    const preview = page.querySelector(
        '[data-vacancy-preview]'
    )

    const getPreviewElement = (selector) => {
        return preview?.querySelector(selector)
    }


    const updatePreview = (vacancy) => {

        if (!preview || !vacancy) {
            return
        }


        preview.hidden = false
        preview.dataset.vacancyId =
            String(vacancy.id)

        const titleLink = getPreviewElement(
            '[data-preview-title]'
        )

        titleLink.textContent = vacancy.title

        titleLink.href =
            page.dataset.vacancyDetailUrl.replace(
                '__ID__',
                vacancy.id
            )


        const companyLink = getPreviewElement(
            '[data-preview-company]'
        )

        if (companyLink) {

            companyLink.textContent =
                vacancy.company

            companyLink.href =
                page.dataset.companyProfileUrl.replace(
                    '__ID__',
                    vacancy.companyId
                )

        }


        getPreviewElement(
            '[data-preview-location]'
        ).textContent =
            vacancy.location


        getPreviewElement(
            '[data-preview-salary-modality]'
        ).textContent =
            `${vacancy.salary} - ${vacancy.modality}`


        getPreviewElement(
            '[data-preview-duration]'
        ).textContent =
            vacancy.stayDuration


        getPreviewElement(
            '[data-preview-job-type]'
        ).textContent =
            vacancy.jobType


        getPreviewElement(
            '[data-preview-description]'
        ).textContent =
            vacancy.description

        const apply = getPreviewElement('[data-preview-apply]')
        const applied = getPreviewElement('[data-preview-applied]')
        if (apply) {
            apply.hidden = Boolean(vacancy.applied)
        }
        if (applied) applied.hidden = !vacancy.applied

        const applicationForm = page.querySelector('[data-application-form]')
        if (applicationForm) {
            applicationForm.action = page.dataset.applicationUrl.replace('__ID__', vacancy.id)
            applicationForm.querySelector('[name="vacancy_id"]').value = String(vacancy.id)
        }

        const bookmark = getPreviewElement('[data-bookmark-form]')
        if (bookmark) {
            bookmark.action = page.dataset.bookmarkUrl.replace('__ID__', vacancy.id)
            bookmark.dataset.vacancyId = String(vacancy.id)
            bookmark.dataset.saved = String(Boolean(vacancy.saved))
            bookmark.querySelector('[data-bookmark-method]').disabled = !vacancy.saved
            const button = bookmark.querySelector('[data-bookmark-button]')
            const label = vacancy.saved ? 'Quitar de guardadas' : 'Guardar vacante'
            button.setAttribute('aria-label', label)
            button.setAttribute('aria-pressed', String(Boolean(vacancy.saved)))
            button.title = label
            bookmark.querySelector('[data-bookmark-image]').src = vacancy.saved ? page.dataset.bookmarkFilledIcon : page.dataset.bookmarkIcon
        }

        /* PRESTACIONES */

        const benefitsContainer =
            getPreviewElement(
                '[data-preview-benefits]'
            )


        benefitsContainer.replaceChildren()


        ;(vacancy.benefits || []).forEach(
            (benefit) => {

                const tag =
                    document.createElement('span')

                tag.className =
                    'vacancy-preview__benefit'

                tag.textContent =
                    benefit

                benefitsContainer.appendChild(tag)

            }
        )




    }


    /*  SELECCIONAR VACANTE  */

    const vacancyCards = page.querySelectorAll(
        '[data-vacancy-card]'
    )


    vacancyCards.forEach((card) => {

        card.addEventListener('click', () => {

            const vacancyId =
                String(card.dataset.vacancyId)

            const vacancy =
                vacancies.find(
                    (item) =>
                        String(item.id) === vacancyId
                )


            if (!vacancy) {
                return
            }


            updatePreview(vacancy)

        })

    })

    if (page.dataset.initialVacancyId) {
        const initial = vacancies.find(item => String(item.id) === page.dataset.initialVacancyId)
        if (initial) updatePreview(initial)
    }

    page.addEventListener('bookmark-changed', event => {
        const vacancy = vacancies.find(item => String(item.id) === String(event.detail.id))
        if (vacancy) vacancy.saved = event.detail.saved
    })

})
