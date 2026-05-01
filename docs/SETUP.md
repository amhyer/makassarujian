# Setup and Installation Guide for Makassarujian Exam Platform

## Local Development Setup
To set up the development environment locally, follow the steps below:

1. **Clone the Repository**  
   Open your terminal and run:
   ```bash
   git clone https://github.com/amhyer/makassarujian.git
   cd makassarujian
   ```

2. **Install Dependencies**  
   Make sure you have Node.js and npm installed, then run:
   ```bash
   npm install
   ```

3. **Run the Development Server**  
   To start the development server, run:
   ```bash
   npm start
   ```
   Your application will be available at `http://localhost:3000`.

## Docker Setup
To set up the application using Docker, follow these steps:

1. **Install Docker**  
   Ensure you have Docker installed on your machine.

2. **Build the Docker Image**  
   Run the following command in the root directory of the project:
   ```bash
   docker build -t makassarujian .
   ```

3. **Run the Docker Container**  
   Execute the following command to start the container:
   ```bash
   docker run -p 3000:3000 makassarujian
   ```
   Access your application at `http://localhost:3000`.

## Database Migrations
To set up the database, perform the following:

1. **Install Database Dependencies**  
   If using PostgreSQL, you should have `pg` and `pg-hstore` installed:
   ```bash
   npm install pg pg-hstore
   ```

2. **Run Migrations**  
   You can run the migrations using:
   ```bash
   npm run migrate
   ```

## Environment Configuration
Create a `.env` file in the root directory and include the following configurations:

```bash
DATABASE_URL=your_database_url
PORT=3000
NODE_ENV=development
```

Replace `your_database_url` with the proper connection string for your database.  

### Conclusion
Following these instructions should help you set up the Makassarujian exam platform for local development and deployment. If you encounter any issues, consult the project's GitHub issues for support or report new issues if needed.
