<script setup>
import { Inertia } from '@inertiajs/inertia'; // Import Inertia for navigation
import { Head } from '@inertiajs/vue3';

// Function to navigate to the form page using GET method
const goToFormPage = () => {
    Inertia.get('/form-page'); // Perform navigation using GET request
};
const sendFollowUpMessages = () => {
    Inertia.post('/send-follow-up-message'); // Perform navigation using GET request
};
</script>

<template>

    <Head title="WallMessenger" />

    <div class="container-fluid home-page-container d-flex flex-column justify-content-center align-items-center">
        <div class="card text-center p-4 shadow-lg bg-white rounded">
            <!-- WhatsApp Business logo -->
            <img
                src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg"
                alt="WhatsApp Logo"
                class="whatsapp-logo mb-3"
            />

            <!-- Introduction message -->
            <h1 class="intro-text mb-4">Welcome to Our Service</h1>
            <p class="lead mb-4">This is a Messenger Service created by FuadKH :)</p>

            <!-- Navigation buttons/links -->
            <div class="button-group">
                <button class="btn btn-outline-success me-2">Button 1</button>
                <button class="btn btn-outline-success me-2">Button 2</button>

                <button @click="sendFollowUpMessages" class="btn btn-outline-success me-2">Follow Up</button>

                <button @click="goToFormPage" class="btn btn-outline-success me-2">Go to Reply Page</button>

            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom styling */
body {
    background-color: #e6ffe6;
}

.home-page-container {
    min-height: 100vh;
    background-color: #e6ffe6;
}

.card {
    max-width: 600px;
    width: 100%;
    padding: 20px;
    background-color: #ffffff;
    border-radius: 15px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
}

.whatsapp-logo {
    width: 100px;
    height: auto;
    transition: transform 0.2s ease-in-out;
}

.whatsapp-logo:hover {
    transform: scale(1.1);
}

.intro-text {
    font-size: 2rem;
    color: #28a745;
    font-weight: 700;
}

.button-group {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
}

.btn-outline-success {
    border: 2px solid #28a745;
    color: #28a745;
    transition: all 0.3s ease-in-out;
}

.btn-outline-success:hover {
    background-color: #28a745;
    color: #fff;
}
</style>
