// DEPRECATED — CI et CD sont séparés :
//   Script Path CI : ci/Jenkinsfile
//   Script Path CD : cd/Jenkinsfile
// Ce fichier reste pour compatibilité et redirige vers la doc.

pipeline {
    agent any
    stages {
        stage('Info') {
            steps {
                echo '''
====================================================
 Utilisez deux jobs Jenkins séparés :

  1) nutritrace-ci  → Script Path = ci/Jenkinsfile
  2) nutritrace-cd  → Script Path = cd/Jenkinsfile

 Document : docs/DevOps-NutriTrace.md
====================================================
'''
                error('Pipeline unique désactivé — configurer ci/Jenkinsfile et cd/Jenkinsfile')
            }
        }
    }
}
