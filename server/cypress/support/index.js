Cypress.on('uncaught:exception', (err, runnable) => {
    // for some reasons, the test fails without this in certain conditions...
    return false
})

//TODO: login remember me and better language support
Cypress.Commands.add("login", (username="admin", password="correcthorsebatterystaple") => {
    cy.setCookie("forceLanguage", "en");
    cy.setCookie('disableServiceWorkerInstallation', '1');

    cy.visit("/");
    cy.get("input[name='name']")
        .clear()
        .type(username)
        .should('have.value', username)

    cy.get("input[name='password']")
        .clear()
        .type(password)
        .should('have.value', password)

    cy.get("input[name='login']").click()
})

Cypress.Commands.add("getApiKey", (username="admin", password="correcthorsebatterystaple") => {
    cy.request({ method: 'POST', url: '/api/login', form: true, body: { username: username, password: password }})
        .then((response) => {
            expect(response.status).to.eq(200)
            expect(response.body).to.have.property('apiKey')
            console.log(response.body)
            return response.body.apiKey
        })
})

beforeEach(() => {
    cy.intercept('https://nominatim.openstreetmap.org/search?format=json&limit=5&q=brescia', { fixture: 'nominatim_brescia.json' });
    cy.intercept('https://nominatim.openstreetmap.org/search?format=json&limit=5&q=milano', { fixture: 'nominatim_milano.json' });
    cy.intercept('https://a.tile.openstreetmap.org/*/*/*.png', { fixture: 'map_frame_A.png' });
    cy.intercept('https://b.tile.openstreetmap.org/*/*/*.png', { fixture: 'map_frame_B.png' });
    cy.intercept('https://c.tile.openstreetmap.org/*/*/*.png', { fixture: 'map_frame_C.png' });
    cy.intercept(Cypress.config('baseUrl')+'resources/ajax/ajax_add_type**').as('ajax_add_type');
    cy.intercept(Cypress.config('baseUrl')+'resources/ajax/ajax_change_availability**').as('ajax_change_availability');
    cy.intercept(Cypress.config('baseUrl')+'resources/ajax/ajax_list**').as('ajax_list');
    cy.intercept(Cypress.config('baseUrl')+'resources/ajax/ajax_log**').as('ajax_log');
    cy.intercept(Cypress.config('baseUrl')+'resources/ajax/ajax_services**').as('ajax_services');
    cy.intercept(Cypress.config('baseUrl')+'resources/ajax/ajax_trainings**').as('ajax_trainings');
});