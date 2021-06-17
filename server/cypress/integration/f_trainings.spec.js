describe("Training management", () => {
    beforeEach(() => {
        cy.login();
    })

    it('Add Training', function () {
        cy.visit("/edit_training?add", {
            onBeforeLoad(win) {
                cy.stub(win, 'prompt').returns('test')
            }
        });
        cy.get('#date-picker').clear();
        cy.get('#date-picker').type('07/12/2020');
        cy.window().then(win => win.$('.datepicker').remove());
        cy.get('#name').clear();
        cy.get('#name').type('Test Training');
        cy.get('#timePicker1').clear();
        cy.get('#timePicker1').type('10:10');
        cy.get('#timePicker2').clear();
        cy.get('#timePicker2').type('23:59');
        cy.get('.chief-5').check();
        cy.get('.crew-2').check();
        cy.get('.crew-4').check();
        cy.get('.crew-3').check();
        cy.get('.crew-6').check();
        cy.get('#addr').clear();
        cy.get('#addr').type('brescia');
        cy.get('#search_button').click();
        cy.get('#search').click();
        cy.get('.results-list > :nth-child(1) > a').click();
        cy.get('[type="submit"]').click();
        cy.wait('@ajax_trainings');
        cy.contains("2020-07-12");
        cy.contains("Test Training");
        cy.visit("/log");
        cy.wait('@ajax_log');
        cy.contains("Training added");
        cy.visit("/list");
        cy.wait('@ajax_list');
    });

    it('Edit Training', function() {
        cy.visit("/trainings");
        cy.wait('@ajax_trainings');
        cy.get('.dtr-details a[data-action="edit"]').first().click();
        cy.get('#name').clear();
        cy.get('#name').type('Training 1 test');
        cy.get('.chief-5').uncheck();
        cy.get('.chief-7').check();
        cy.get('.crew-3').uncheck();
        cy.get('.crew-6').uncheck();
        cy.get('.crew-9').check();
        cy.get('.crew-8').check();
        cy.get('#addr').clear();
        cy.get('#addr').type('milano');
        cy.get('#search_button').click();
        cy.get('.results-list > :nth-child(1) > a').click();
        cy.get('[type="submit"]').click();
        cy.wait('@ajax_trainings');
        cy.contains("2020-07-12");
        cy.contains("Training 1 test");
        cy.visit("/log");
        cy.wait('@ajax_log');
        cy.contains("Training edited");
    });

    it('Delete Training', function() {
        cy.visit("/trainings");
        cy.wait('@ajax_trainings');
        cy.get('.dtr-details a[data-action="delete"]').first().click();
        cy.get('#remove').click();
        cy.wait('@ajax_trainings');
        cy.visit("/log");
        cy.wait('@ajax_log');
        cy.contains("Training removed");
    });

})