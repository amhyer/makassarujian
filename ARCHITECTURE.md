# System Architecture Documentation

## Overview
This document provides a comprehensive overview of the system architecture for the Makassar Ujian application. It details the technology stack, component interactions, and deployment strategies.

## Tech Stack
- **Frontend**: React.js
- **Backend**: Node.js with Express
- **Database**: MongoDB
- **Authentication**: JWT (JSON Web Tokens)
- **Hosting**: AWS (Amazon Web Services)

## Component Interactions
1. **User Interface**: The frontend interacts with the backend API using HTTP requests (RESTful).
2. **Server**: The Node.js server processes incoming requests and communicates with the database.
3. **Database**: All application data is stored in MongoDB. The server queries or updates database records as necessary.
4. **Authentication**: Users are authenticated using JWT, which is issued upon successful login.
5. **Deployment**: The entire application is deployed on AWS services including EC2 for servers and MongoDB Atlas for the database.

## Deployment Details
- **Environment**: Production
- **Service**: The application will be hosted on an EC2 instance running Node.js.
- **Database**: Configured using MongoDB Atlas for easy scaling and management.
- **CI/CD**: Utilizes GitHub Actions for continuous integration and deployment.

## Conclusion
This document serves as a base understanding of the system architecture for developers and stakeholders in the Makassar Ujian project.