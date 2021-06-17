describe("Service management", () => {
    beforeEach(() => {
        cy.login();
    })

    it('Add Service with new type', function () {
        cy.get('tr:has(> td:has(> [data-user="11"])) > :nth-child(6)').should('contain', '0');
        cy.get('tr:has(> td:has(> [data-user="4"])) > :nth-child(6)').should('contain', '0');
        cy.get('tr:has(> td:has(> [data-user="9"])) > :nth-child(6)').should('contain', '0');
        cy.get('tr:has(> td:has(> [data-user="7"])) > :nth-child(6)').should('contain', '0');
        cy.get('tr:has(> td:has(> [data-user="2"])) > :nth-child(6)').should('contain', '0');
        cy.get('tr:has(> td:has(> [data-user="6"])) > :nth-child(6)').should('contain', '0');
        cy.visit("/edit_service?add", {
            onBeforeLoad(win) {
                cy.stub(win, 'prompt').returns('test');
            }
        });
        cy.get('#date-picker').clear();
        cy.get('#date-picker').type('07/12/2020');
        cy.window().then(win => win.$('.datepicker').remove());
        cy.get('#progressivo').clear();
        cy.get('#progressivo').type('1234/5');
        cy.get('#timePicker1').clear();
        cy.get('#timePicker1').type('10:10');
        cy.get('#timePicker2').clear();
        cy.get('#timePicker2').type('23:59');
        cy.get('.chief-11').check();
        cy.get('.drivers-7').check();
        cy.get('.drivers-6').check();
        cy.get('.crew-2').check();
        cy.get('.crew-4').check();
        cy.get('.crew-9').check();
        cy.get('#addr').clear();
        cy.get('#addr').type('brescia');
        cy.get('#search_button').click();
        cy.get('#search').click();
        cy.get('.results-list > :nth-child(1) > a').click();
        cy.get('#notes').click();
        cy.get('#types').select('add_new');
        cy.wait('@ajax_add_type');
        cy.get('[type="submit"]').click();
        cy.wait('@ajax_services');
        cy.contains("2020-07-12");
        cy.contains("1234/5");
        cy.visit("/log");
        cy.wait('@ajax_log');
        cy.contains("Service added");
        cy.visit("/list");
        cy.wait('@ajax_list');
        cy.get('tr:has(> td:has(> [data-user="11"])) > :nth-child(6)').should('contain', '1');
        cy.get('tr:has(> td:has(> [data-user="4"])) > :nth-child(6)').should('contain', '1');
        cy.get('tr:has(> td:has(> [data-user="9"])) > :nth-child(6)').should('contain', '1');
        cy.get('tr:has(> td:has(> [data-user="7"])) > :nth-child(6)').should('contain', '1');
        cy.get('tr:has(> td:has(> [data-user="2"])) > :nth-child(6)').should('contain', '1');
        cy.get('tr:has(> td:has(> [data-user="6"])) > :nth-child(6)').should('contain', '1');
    });

    it('Edit service', function() {
        cy.visit("/services");
        cy.wait('@ajax_services');
        cy.get('.dtr-details a[data-action="edit"]').first().click();
        cy.get('#progressivo').clear();
        cy.get('#progressivo').type('4321/5');
        cy.get('.chief-11').uncheck();
        cy.get('.chief-8').check();
        cy.get('.crew-4').uncheck();
        cy.get('.crew-9').uncheck();
        cy.get('.crew-3').check();
        cy.get('.crew-5').check();
        cy.get('#addr').clear();
        cy.get('#addr').type('milano');
        cy.get('#search_button').click();
        cy.get('.results-list > :nth-child(1) > a').click();
        cy.get('[type="submit"]').click();
        cy.wait('@ajax_services');
        cy.contains("2020-07-12");
        cy.contains("4321/5");
        cy.visit("/log");
        cy.wait('@ajax_log');
        cy.contains("Service edited");
        cy.visit("/list");
        cy.wait('@ajax_list');
        cy.get('tr:has(> td:has(> [data-user="11"])) > :nth-child(6)').should('contain', '0');
        cy.get('tr:has(> td:has(> [data-user="8"])) > :nth-child(6)').should('contain', '1');
        cy.get('tr:has(> td:has(> [data-user="4"])) > :nth-child(6)').should('contain', '0');
        cy.get('tr:has(> td:has(> [data-user="9"])) > :nth-child(6)').should('contain', '0');
        cy.get('tr:has(> td:has(> [data-user="3"])) > :nth-child(6)').should('contain', '1');
        cy.get('tr:has(> td:has(> [data-user="5"])) > :nth-child(6)').should('contain', '1');
    });

    it('Delete Service', function() {
        cy.visit("/services");
        cy.wait('@ajax_services');
        cy.get('.dtr-details a[data-action="delete"]').first().click();
        cy.get('#remove').click();
        cy.wait('@ajax_services');
        cy.visit("/log");
        cy.wait('@ajax_log');
        cy.contains("Service removed");
        cy.visit("/list");
        cy.wait('@ajax_list');
        cy.get('tr:has(> td:has(> [data-user="8"])) > :nth-child(6)').should('contain', '0');
        cy.get('tr:has(> td:has(> [data-user="3"])) > :nth-child(6)').should('contain', '0');
        cy.get('tr:has(> td:has(> [data-user="5"])) > :nth-child(6)').should('contain', '0');
        cy.get('tr:has(> td:has(> [data-user="7"])) > :nth-child(6)').should('contain', '0');
        cy.get('tr:has(> td:has(> [data-user="2"])) > :nth-child(6)').should('contain', '0');
        cy.get('tr:has(> td:has(> [data-user="6"])) > :nth-child(6)').should('contain', '0');
    });

})