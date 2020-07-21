describe('multiple', () => {
  it('should display 2 h5ps', () => {

    cy.visit('test/multiple.html');

    cy.get('#h5p-container-1 .h5p-iframe').should(iframe => {
      expect(iframe.contents().find('.h5p-content')).to.exist;
    });

    cy.get('#h5p-container-2 .h5p-iframe').should(iframe => {
      expect(iframe.contents().find('.h5p-content')).to.exist;
    });
  });
});