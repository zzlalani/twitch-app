# Twitch App

Twitch Api Webservices Implementation with PHP7


[Demo](https://twitch-app-2.herokuapp.com)

### Setup

    git clone https://github.com/zzlalani/twitch-app.git
    cd twitch-app
    composer install
    
### Server Requirments

- PHP 7.2.x or above
- Apache 2.4.x or above

#### Library Used

- [Slim Framework ^3.0](https://github.com/slimphp/Slim) Light and Fast PHP Framework
- [New Twitch API (Helix) ^2.1](https://github.com/nicklaw5/twitch-api-php) Twitch Client API for PHP7
- [slim/twig-view ^2.4](https://github.com/slimphp/Twig-View) Twig View Templates for Slim Framework

#### Questions

- How would you deploy the above on AWS? (ideally a rough architecture diagram will help)

![Deployment flow](https://i.ibb.co/gJrNXhc/Diagram-Q1.png "Deployment flow")

> From the local development server to github repository and pull the application code to AWS EC2 Instance

- Where do you see bottlenecks in your proposed architecture and how would you approach scaling this app starting from 100 reqs/day to 900MM reqs/day over 6 months?

![Deployment flow with Load balancing](https://i.ibb.co/8s7dS8H/Diagram-Q2.png "Deployment flow with Load balancing")

> AWS elastic Load balancer should be configure with number of instance, the load balancer init EC2 instance as per need and put down when not needed
