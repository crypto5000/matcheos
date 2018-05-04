const Nightmare = require('nightmare')
const assert = require('assert')

describe('Page Tests', function() {
  // Recommended: 5s locally, 10s to remote server, 30s from airplane ¯\_(ツ)_/¯
  this.timeout('30s')

  let nightmare = null
  beforeEach(() => {
    nightmare = new Nightmare({ show: false })
  })

  describe('/ (Signup Exists)', () => {
    it('should load without error', done => {      
      nightmare.goto('http://localhost/matcheos/signup.php')
        .end()
        .then(function (result) { done() })
        .catch(done)
    })
  })

  describe('/ (Login Exists)', () => {
    it('should load without error', done => {      
      nightmare.goto('http://localhost/matcheos/index.php')
        .end()
        .then(function (result) { done() })
        .catch(done)
    })
  })

  describe('Signup Page', function () {
    describe('given bad data', () => {
      it('should fail', done => {
        nightmare
        .goto('http://localhost/matcheos/signup.php')
        .on('page', (type, message) => {
          if (type == 'alert') done()
        })
        .type('#accountName', 'bytemaster')
        .type('#email', 'invalidemail')
        .click('#signupButton')
        .wait(2000)
        .end()
        .then()
        .catch(done)
      })
    })
  })

  describe('Signup Page', function () {
    describe('sign up with good data', () => {
      it('should pass', done => {
        nightmare
        .goto('http://localhost/matcheos/signup.php')
        .on('page', (type, message) => {
          if (type == 'alert') done()
        })
        .type('#accountName', 'bytemaster1')
        .type('#email', 'example1@example.com')
        .click('#signupButton')
        .wait(1000)
        .click('#acceptPasskey')
        .wait(5000)
        .wait('#createProfile') // this selector only appears on the create profile page
        .end()
        .then(result => { done() })
        .catch(done)        
      })
    })
  })

  describe('Login Page', function () {
    describe('login with bad data', () => {
      it('should fail', done => {
        nightmare
        .goto('http://localhost/matcheos/index.php')
        .on('page', (type, message) => {
          if (type == 'alert') done()
        })
        .type('#email', 'bytemaster')
        .type('#passKey', 'example')
        .click('#loginClick')
        .end()      
        .catch(done)        
      })
    })
  })

  describe('Login Page', function () {
    describe('login with more bad data', () => {
      it('should fail', done => {
        nightmare
        .goto('http://localhost/matcheos/index.php')
        .on('page', (type, message) => {
          if (type == 'alert') done()
        })
        .type('#email', 'bytemaster@email.com')
        .type('#passKey', 'example')
        .click('#loginClick')
        .end()        
        .catch(done)        
      })
    })
  })

  describe('Login Page', function () {
    describe('login with more bad data', () => {
      it('should fail', done => {
        nightmare
        .goto('http://localhost/matcheos/index.php')
        .on('page', (type, message) => {
          if (type == 'alert') done()
        })
        .type('#email', 'bytemaster@email.com')
        .type('#passKey', 'exampleasadfasdfasdfsdfsasdf')
        .click('#loginClick')
        .end()        
        .catch(done)        
      })
    })
  })

  describe('Login Page', function () {
    describe('login with more bad data', () => {
      it('should fail', done => {
        nightmare
        .goto('http://localhost/matcheos/index.php')
        .on('page', (type, message) => {
          if (type == 'alert') done()
        })
        .type('#email', 'bytemaster@email.com')
        .type('#passKey', '0123456789012345678901234567890123456789012345678901234567891234')
        .click('#loginClick')
        .end()        
        .then(result => { done() })
        .catch(done)        
      })
    })
  })

  describe('/ (Privacy Exists)', () => {
    it('should load without error', done => {      
      nightmare.goto('http://localhost/matcheos/privacy.html')
        .end()
        .then(function (result) { done() })
        .catch(done)
    })
  })

  describe('/ (Code of Conduct Exists)', () => {
    it('should load without error', done => {      
      nightmare.goto('http://localhost/matcheos/code.html')
        .end()
        .then(function (result) { done() })
        .catch(done)
    })
  })


})

