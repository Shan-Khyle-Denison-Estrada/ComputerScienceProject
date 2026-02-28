#!/bin/bash

# Load the Elastic Beanstalk environment variables
source /opt/elasticbeanstalk/deployment/env

# Run migrations and safely seed the database
php artisan migrate --seed --force

# Create the public storage shortcut for images
php artisan storage:link --force