/**
 * Profile Completion Check
 * Include this script in all dashboard/feature pages
 * Redirects to complete-profile.html if profile is incomplete
 */

(function() {
    const userDataStr = localStorage.getItem('agrohub_user');
    
    if (!userDataStr) {
        // No user logged in, redirect to login
        window.location.href = 'login.html';
        return;
    }

    const userData = JSON.parse(userDataStr);
    
    // Check if profile is incomplete
    const profileIncomplete = !userData.phone || 
                             userData.phone === '' || 
                             userData.profile_completed === false || 
                             userData.profile_completed === 0;
    
    // Get current page
    const currentPage = window.location.pathname.split('/').pop();
    
    // Don't redirect if already on complete-profile page
    if (currentPage !== 'complete-profile.html' && profileIncomplete) {
        console.log('Profile incomplete, redirecting to complete-profile.html');
        window.location.href = 'complete-profile.html';
    }
})();
