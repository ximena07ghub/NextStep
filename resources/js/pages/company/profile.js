document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('[data-company-profile]')

    if (!page) {
        return
    }

    const form = page.querySelector('[data-company-profile-form]')

    if (!form) {
        return
    }


    // elementos

    const editableFields = page.querySelectorAll(
        '[data-company-editable]'
    )

    const editButton = page.querySelector(
        '[data-company-edit]'
    )

    const saveButton = page.querySelector(
        '[data-company-save]'
    )

    const cancelButton = page.querySelector(
        '[data-company-cancel]'
    )


    // sector

    const sectorView = page.querySelector(
        '[data-company-sector-view]'
    )

    const sectorSelect = page.querySelector(
        '[data-company-sector]'
    )

    const customSectorWrapper = page.querySelector(
        '[data-company-custom-sector-wrapper]'
    )

    const customSectorInput = page.querySelector(
        '[data-company-custom-sector]'
    )


    // contraseña

    const passwordFields = page.querySelector(
        '[data-company-password-fields]'
    )

    const passwordInputs = page.querySelectorAll(
        '[data-company-password]'
    )


    // descripción

    const descriptionView = page.querySelector(
        '[data-company-description-view]'
    )

    const descriptionInput = page.querySelector(
        '[data-company-description-input]'
    )


    // logo

    const logoInput = page.querySelector(
        '[data-company-logo]'
    )

    const companyImage = page.querySelector(
        '[data-company-image]'
    )


    let logoPreviewUrl = null


    // descripción automática

    const resizeDescription = () => {
        if (!descriptionInput) {
            return
        }

        descriptionInput.style.height = 'auto'

        descriptionInput.style.height =
            `${descriptionInput.scrollHeight}px`
    }


    // sector "otro"

    const updateCustomSector = () => {
        if (
            !sectorSelect ||
            !customSectorWrapper ||
            !customSectorInput
        ) {
            return
        }

        const isEditing =
            page.classList.contains('is-editing')

        const isOther =
            sectorSelect.value === 'other'

        const shouldShow =
            isEditing && isOther

        customSectorWrapper.hidden = !shouldShow
        customSectorInput.disabled = !shouldShow
    }


    // modo edición

    const setEditing = (isEditing) => {
        page.classList.toggle(
            'is-editing',
            isEditing
        )


        // estado y ciudad

        editableFields.forEach(field => {
            field.readOnly = !isEditing
        })


        // sector

        if (sectorView) {
            sectorView.hidden = false
        }

        if (sectorSelect) {
            sectorSelect.hidden = !isEditing
            sectorSelect.disabled = !isEditing
        }

        updateCustomSector()


        // contraseña

        if (passwordFields) {
            passwordFields.hidden = !isEditing
        }

        passwordInputs.forEach(input => {
            input.disabled = !isEditing
        })


        // descripción

        if (descriptionView) {
            descriptionView.hidden = isEditing
        }

        if (descriptionInput) {
            descriptionInput.hidden = !isEditing
            descriptionInput.disabled = !isEditing
        }


        // logo

        if (logoInput) {
            logoInput.disabled = !isEditing
        }


        // botones

        if (editButton) {
            editButton.hidden = isEditing
        }

        if (saveButton) {
            saveButton.hidden = !isEditing
        }

        if (cancelButton) {
            cancelButton.hidden = !isEditing
        }


        if (isEditing) {
            requestAnimationFrame(() => {
                resizeDescription()
            })
        }
    }


    // editar

    editButton?.addEventListener('click', () => {
        setEditing(true)

        descriptionInput?.focus()
    })


    // cancelar

    cancelButton?.addEventListener('click', () => {

        window.location.reload()
    })


    // cambio de sector

    sectorSelect?.addEventListener(
        'change',
        updateCustomSector
    )


    // descripción

    descriptionInput?.addEventListener(
        'input',
        resizeDescription
    )


    // preview del logo

    logoInput?.addEventListener('change', () => {
        const file = logoInput.files?.[0]

        if (!file || !companyImage) {
            return
        }

        if (!file.type.startsWith('image/')) {
            return
        }

        if (logoPreviewUrl) {
            URL.revokeObjectURL(logoPreviewUrl)
        }

        logoPreviewUrl =
            URL.createObjectURL(file)

        companyImage.src =
            logoPreviewUrl
    })


    // estado inicial

    const initialEdit =
        page.dataset.companyInitialEdit === 'true'

    setEditing(initialEdit)


    // limpieza

    window.addEventListener('beforeunload', () => {
        if (logoPreviewUrl) {
            URL.revokeObjectURL(logoPreviewUrl)
        }
    })
})