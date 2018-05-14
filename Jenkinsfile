pipeline {
  agent any
  stages {
    stage('Checkout Suite') {
      steps {
        sh '''pwd
ls -al'''
        git(url: 'git@bitbucket.org:divvit/divvit-plugin-magento2-suite.git', branch: 'master')
        sh '''pwd
ls -al'''
      }
    }
  }
}