#!/bin/bash

# Load the Elastic Beanstalk environment variables
source /opt/elasticbeanstalk/deployment/env

# Rebuild the storage link after any AWS configuration changes
php artisan storage:link --force