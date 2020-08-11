# Change Log

## v1.1.0 / 2018-09-24
### Added
- bar suuport. #71

### Fixed
- support IE11 #74

## v1.0.1 / 2018-08-27
### Fixed
- tries to render labels on line-chart #70
- reop: Label possition conflict when using doughnutlabel plugin. #67

## v1.0.0 / 2018-08-21
### Changed
- package name and usage follows Chart.js plugin rules. #66
- option `overlap` default true.
- require Chart.js 2.6.0+

### Removed
- option `mode`
- option `format`

### Improve
- refactor code structure.

## v0.15.0 / 2018-08-18
### Fixed
- Label possition conflict when using doughnutlabel plugin. #67

### Added
- polarArea suuport. #37

## v0.14.1 / 2018-08-12
### Fixed
- text.split is not a function.

## v0.14.0 / 2018-08-08
### Added
- multiple options support. #18

## v0.13.0 / 2018-08-07
### Added
- multiple lines support. #31

## v0.12.0 / 2018-07-30
### Fixed
- outside label position. #45, #61
- 'afterDatasetsDraw' of undefined #60

## v0.11.0 / 2018-06-10
### Added
- option `textMargin`. #54
- option text shadow. #56

## v0.10.0 / 2017-11-19
### Added
- option `outsidePadding`. #42

### Fixed
- render stopped if label is empty.

## v0.9.0 / 2017-11-19
### Added
- option `showActualPercentages`. #42

### Fixed
- numbers as labels. #38

## v0.8.2 / 2017-10-25
### Added
- dataset and index parameters to `render`. #35
- Chart not defined check for SSR #33

## v0.8.1 / 2017-09-07
### Added
- dataset and index parameters to `fontColor`. #32

## v0.8.0 / 2017-08-29
### Added
- option `fontColor` can be function. #29
- option `render` can be 'image'. #19

## v0.7.0 / 2017-08-03
### Added
- option `overlap`. # 25
- option `render` can be custom function. #21, #24
- option `fontColor` can be array. #20

### Changed
- option `mode` rename to `render`, `mode` still works.

### Deprecated
- option `mode`.
- option `format`.

## v0.6.0 / 2017-07-07
### Added
- option `showZero`. # 14

### Fixed
- outside label overlap in some cases.

## v0.5.0 / 2017-06-15
### Fixed
- bug #12

### Changed
- drop support for chart.js below v2.1.5

## v0.4.0 / 2017-05-26
### Added
- option `position`, available value is 'default', 'border' and 'outside'. # 8

### Changed
- option `arcText` rename to `arc`.
- option `borderText` remove and replace by `position` with value 'border'.

## v0.3.0 / 2017-04-10
### Added
- borderText feature. #2

### Fixed
- percentage not visible on last segment of chart. #3, #4

## v0.2.0 / 2017-03-02
### Added
- arcText feature.
- format feature.

## v0.1.0 / 2017-01-11
### Added
- First version implementation.
