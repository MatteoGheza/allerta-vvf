describe("User management", () => {
    before(() => {
        cy.login()
        cy.fixture('users')
            .as('users');
    })
    it('Create users', () => {
        cy.get('@users')
            .then((users) => {
                cy.getApiKey().then((apiKey) => {
                    var i = 1
                    var phone_number = "+1-202-555-0100"; // https://fakenumber.org/
                    users.forEach(user => {
                        console.log("User '"+user.name+"' number "+i);
                        if(i == 1){
                            console.log("Adding user via gui...");
                            name = user.name
                            console.log(user)
                            cy.wait(1000)
                            cy.contains("Add user").click()
                            cy.get("input[name='mail']")
                                .clear()
                                .type(user.email)
                                .should('have.value', user.email)
                            cy.get("input[name='name']")
                                .clear()
                                .type(user.name)
                                .should('have.value', user.name)
                            cy.get("input[name='username']")
                                .clear()
                                .type(user.username)
                                .should('have.value', user.username)
                            cy.get("input[name='password']")
                                .clear()
                                .type(user.password)
                                .should('have.value', user.password)
                            cy.get("input[name='phone_number']")
                                .clear()
                                .type(phone_number)
                                .should('have.value', phone_number)
                            cy.get("input[name='birthday']")
                                .clear()
                                .type(user.birthday)
                                .should('have.value', user.birthday)
                            if(user.chief){
                                cy.get("input[name='chief']")
                                    .check({force: true})
                            }
                            if(user.driver){
                                cy.get("input[name='driver']")
                                    .check({force: true})
                            }
                            cy.contains("Submit").click()
                            cy.wait('@ajax_list')
                            cy.contains(user.name)
                            cy.visit("/log")
                            cy.wait('@ajax_log')
                            cy.contains("User added")
                            cy.contains(user.name)
                            cy.visit("/")
                        } else {
                            console.log("Adding user via api...");
                            cy.request({ method: 'POST', url: '/api/user', form: true,
                            body: {
                                apiKey: apiKey,
                                mail: user.email,
                                name: user.name,
                                username: user.username,
                                password: user.password,
                                phone_number: phone_number,
                                birthday: user.birthday,
                                chief: user.chief | 0,
                                driver: user.driver | 0,
                                hidden: 0,
                                disabled: 0
                            }})
                                .then((response) => {
                                    console.log(response.body)
                                    expect(response.status).to.eq(200)
                                    expect(response.body).to.have.property('userId')
                                    cy.visit("/log")
                                    cy.wait('@ajax_log')
                                    cy.contains("User added")
                                    cy.contains(user.name)
                                })
                        }
                        i+=1;
                    })
                });
            })
    });
})
