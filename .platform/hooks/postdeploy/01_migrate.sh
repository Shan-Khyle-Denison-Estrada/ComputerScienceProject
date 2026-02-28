#!/bin/bash

# Load the Elastic Beanstalk environment variables
source /opt/elasticbeanstalk/deployment/env

# Run migrations and safely seed the database
php artisan migrate --seed --force