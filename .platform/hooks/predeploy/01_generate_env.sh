#!/bin/bash

# Fetch environment variables from Elastic Beanstalk and format them into a standard .env file
sudo /opt/elasticbeanstalk/bin/get-config environment | jq -r 'to_entries | .[] | "\(.key)=\(.value)"' > /var/app/staging/.env

# Ensure the webapp user has the correct permissions to read the new .env file
sudo chown webapp:webapp /var/app/staging/.env
sudo chmod 644 /var/app/staging/.env