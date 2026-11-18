// cambia entre las vacantes y las valoraciones reales de la empresa
const companyPage = document.querySelector('[data-company-public-profile]')
if (companyPage) {
    const tabs = companyPage.querySelectorAll('[data-profile-tab]')
    const sections = companyPage.querySelectorAll('[data-profile-section]')
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(item => {
                const active = item === tab
                item.classList.toggle('company-public-profile__tab--active', active)
                item.setAttribute('aria-selected', String(active))
            })
            sections.forEach(section => {
                section.hidden = section.dataset.profileSection !== tab.dataset.profileTab
            })
        })
    })
}
