export default class SubmissionError extends Error {
  constructor(errors) {
    console.log("SubmissionError class")
    super("Submit Validation Failed")
    this.errors = errors
    Error.captureStackTrace(this, this.constructor)
    this.name = this.constructor.name

    return this
  }
}
