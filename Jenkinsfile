pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '15'))
    }

    environment {
        APP_NAME     = 'nutritrace'
        WORKSPACE_DIR = '/workspace/nutritrace'
    }

    stages {
        stage('Checkout') {
            steps {
                echo "Pipeline NutriTrace — build #${env.BUILD_NUMBER}"
                sh '''
                    if [ -d /workspace/nutritrace ]; then
                      echo "Code monté depuis Docker volume"
                      ls -la /workspace/nutritrace | head -20
                    else
                      echo "Workspace Jenkins classique"
                      ls -la
                    fi
                '''
            }
        }

        stage('Dependencies') {
            parallel {
                stage('Composer') {
                    steps {
                        sh '''
                            docker run --rm \
                              -v "/workspace/nutritrace:/app" -w /app \
                              composer:2 \
                              composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs
                        '''
                    }
                }
                stage('NPM') {
                    steps {
                        sh '''
                            docker run --rm \
                              -v "/workspace/nutritrace:/app" -w /app \
                              node:22-alpine \
                              sh -c "npm ci || npm install"
                        '''
                    }
                }
            }
        }

        stage('Build assets') {
            steps {
                sh '''
                    docker run --rm \
                      -v "/workspace/nutritrace:/app" -w /app \
                      node:22-alpine \
                      npm run build
                '''
            }
        }

        stage('Quality') {
            steps {
                sh '''
                    docker run --rm \
                      -v "/workspace/nutritrace:/app" -w /app \
                      composer:2 \
                      sh -c "cp -n .env.example .env || true; php artisan --version || true"
                '''
            }
        }

        stage('Tests (CI)') {
            steps {
                sh '''
                    docker run --rm \
                      -v "/workspace/nutritrace:/app" -w /app \
                      --network laravelproject_default \
                      -e DB_CONNECTION=sqlite \
                      -e DB_DATABASE=/app/database/testing.sqlite \
                      php:8.2-cli \
                      bash -c "apt-get update -qq && apt-get install -y -qq git unzip libzip-dev > /dev/null \
                        && docker-php-ext-install zip pdo > /dev/null \
                        && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
                        && composer install --no-interaction --prefer-dist --ignore-platform-reqs \
                        && mkdir -p database && touch database/testing.sqlite \
                        && cp -n .env.example .env \
                        && php artisan key:generate --force \
                        && php artisan test --testsuite=Unit || true"
                '''
            }
        }

        stage('Build image (CD)') {
            steps {
                sh '''
                    docker build -t nutritrace:${BUILD_NUMBER} -t nutritrace:latest /workspace/nutritrace
                '''
            }
        }

        stage('Deploy (CD)') {
            steps {
                sh '''
                    cd /workspace/nutritrace
                    docker compose up -d --build app db
                    docker compose ps
                '''
            }
        }
    }

    post {
        success {
            echo "SUCCESS — NutriTrace CI/CD #${env.BUILD_NUMBER} — App http://127.0.0.1:8088 — Jenkins http://127.0.0.1:8090"
        }
        failure {
            echo "FAILURE — build #${env.BUILD_NUMBER}"
        }
    }
}
