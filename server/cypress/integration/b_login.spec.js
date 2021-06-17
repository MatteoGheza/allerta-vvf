describe("Login and logout", () => {
    it('Login', function () {
        cy.login()
        cy.contains("Logs")
        cy.contains("Logs").click()
        cy.wait('@ajax_log')
        cy.get("#list").contains("Login")
    })

    it('Logout', function () {
        cy.login()
        cy.visit("/logout")
        cy.contains("Login")
        cy.login()
        cy.contains("Logs")
        cy.contains("Logs").click()
        cy.wait('@ajax_log')
        cy.get("#list").contains("Logout")
    })
});