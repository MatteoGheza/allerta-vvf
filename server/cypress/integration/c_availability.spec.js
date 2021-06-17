describe("Availability", () => {
    beforeEach(() => {
        cy.login()
    })
    it('Change availability to available', function () {
        cy.contains('Activate').click()
        cy.wait("@ajax_change_availability")
        cy.get(".toast-message").should('be.visible').contains('Thanks, admin, you have given your availability in case of alert.');
        cy.wait("@ajax_list")
        cy.get(".fa-check").should('be.visible')
        cy.visit("/log")
        cy.wait("@ajax_log")
        cy.contains("Status changed to 'available'")
        cy.visit("/")
    })

    it('Change availability to not available', function () {
        cy.contains('Deactivate').click()
        cy.wait("@ajax_change_availability")
        cy.get(".toast-message").should('be.visible').contains('Thanks, admin, you have removed your availability in case of alert.');
        cy.wait("@ajax_list")
        cy.get(".fa-times").should('be.visible')
        cy.visit("/log")
        cy.wait("@ajax_log")
        cy.contains("Status changed to 'not available'")
        cy.visit("/")
    })
});