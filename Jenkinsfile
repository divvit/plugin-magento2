pipeline {
  agent any
  stages {
    stage('Checkout Suite') {
      steps {
        git(url: 'git@bitbucket.org:divvit/divvit-plugin-magento2-suite.git', branch: 'master')
        echo 'Hello'
        sh 'ls'
      }
    }
  }
}
