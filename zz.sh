#!/bin/bash
# MyDaughter Project Deployment Script
# This script handles git operations and remote deployment

# Default commit message
COMMIT_MSG="update"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Remote server details
REMOTE_USER="root"
REMOTE_HOST="oop.vn"
REMOTE_PATH="/var/www/vhosts/my-daughter"

# Function to display messages
print_message() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Check if a custom commit message was provided
if [ $# -eq 1 ]; then
    COMMIT_MSG="$1"
fi

# Function for Git operations
perform_git_operations() {
    print_message "Starting git operations..."

    # Add all changes
    print_message "Adding changes to git..."
    if git add .; then
        print_message "Changes added successfully."
    else
        print_error "Failed to add changes to git."
        return 1
    fi

    # Commit changes
    print_message "Committing with message: '${COMMIT_MSG}'..."
    if git commit -m "${COMMIT_MSG}"; then
        print_message "Changes committed successfully."
    else
        print_warning "No changes to commit or commit failed."
    fi

    # Push changes
    print_message "Pushing to remote repository (branch: master2)..."
    if git push origin master2; then
        print_message "Changes pushed successfully."
    else
        print_error "Failed to push changes."
        return 1
    fi

    return 0
}

# Function for SSH deployment
deploy_to_remote() {
    print_message "Deploying to remote server: ${REMOTE_HOST}..."

    # Execute SSH command to pull latest changes
    ssh ${REMOTE_USER}@${REMOTE_HOST} "cd ${REMOTE_PATH} && git pull"

    if [ $? -eq 0 ]; then
        print_message "Remote deployment completed successfully."
    else
        print_error "Remote deployment failed."
        return 1
    fi

    return 0
}

# Function to run build on remote server
run_build_on_remote() {
    print_message "Running build process on remote server..."
    # Execute SSH command to run npm build
    ssh ${REMOTE_USER}@${REMOTE_HOST} "cd ${REMOTE_PATH} && npm i"

    # Execute SSH command to run npm build
    ssh ${REMOTE_USER}@${REMOTE_HOST} "cd ${REMOTE_PATH} && npm run build"

    if [ $? -eq 0 ]; then
        print_message "Build process completed successfully."
    else
        print_error "Build process failed."
        return 1
    fi

    # Run Laravel migrations
    print_message "Running database migrations..."
    ssh ${REMOTE_USER}@${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan migrate --force"

    if [ $? -eq 0 ]; then
        print_message "Database migrations completed successfully."
    else
        print_warning "Database migrations failed or nothing to migrate."
    fi

    # Clear Laravel caches
    print_message "Clearing Laravel caches..."
    ssh ${REMOTE_USER}@${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan cache:clear && php artisan config:clear"

    if [ $? -eq 0 ]; then
        print_message "Laravel caches cleared successfully."
    else
        print_warning "Failed to clear Laravel caches."
    fi

    # Restart Apache HTTP service
    print_message "Restarting Apache HTTP service..."
    ssh ${REMOTE_USER}@${REMOTE_HOST} "systemctl restart httpd"

    if [ $? -eq 0 ]; then
        print_message "Apache HTTP service restarted successfully."
    else
        print_error "Failed to restart Apache HTTP service."
        return 1
    fi

    return 0
}

# Main script execution
main() {
    print_message "Starting deployment process..."

    # Perform git operations
    if perform_git_operations; then
        print_message "Git operations completed successfully."
    else
        print_error "Git operations failed, aborting deployment."
        exit 1
    fi

    # Deploy to remote server
    if deploy_to_remote; then
        print_message "Deployment completed successfully."
    else
        print_error "Deployment failed."
        exit 1
    fi

    # Run build process on remote server
    if run_build_on_remote; then
        print_message "Build process completed successfully."
    else
        print_error "Build process failed."
        exit 1
    fi

    print_message "All operations completed successfully!"
}

# Run the main function
main

# Usage: ./zz.sh "Your custom commit message"
