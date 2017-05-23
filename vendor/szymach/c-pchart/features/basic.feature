Feature: Basic functionality
    As a user I want to be able to create empty images, basic charts
    Also, I want to be able to render them to a file, as well as output them to the browser

    Scenario: Creating empty images
        Given the output directory is empty
        Then I should be able to create empty images of width "700" and height "400"

    Scenario: Creating a spline chart
        Given I render the chart of type "spline"
        Then I should see a new file "example.png" in output folder

    Scenario: Outputting a chart to the browser
        Given I open the "Index" page
        Then there should be a "Content-type" header with value "image/png" set in the response

