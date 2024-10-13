<script setup>
import { ref } from 'vue';
import { Inertia } from '@inertiajs/inertia';

// Define form data as reactive references
const mobile = ref('');
const specificMessage = ref('');
const answerMessage = ref('');

// Form submission handler
const submitForm = () => {
    Inertia.post('/reply-message', {
        mobile: mobile.value,
        specificMessage: specificMessage.value,
        answerMessage: answerMessage.value,
    });
};
</script>

<template>
    <div class="container-fluid form-page-container d-flex flex-column justify-content-center align-items-center">
        <div class="card p-4 shadow-lg bg-white rounded">
            <!-- Form Heading -->
            <h1 class="text-center mb-4 text-success">Submit Your Information</h1>

            <!-- Form Fields -->
            <form @submit.prevent="submitForm" class="form-content">
                <!-- Mobile Number Input -->
                <div class="mb-3">
                    <label for="mobile" class="form-label">Mobile Number</label>
                    <input
                        type="text"
                        v-model="mobile"
                        class="form-control"
                        id="mobile"
                        placeholder="Enter mobile number"
                    />
                </div>

                <!-- Specific Message to Read Input -->
                <div class="mb-3">
                    <label for="specificMessage" class="form-label">Specific Message to Read</label>
                    <input
                        type="text"
                        v-model="specificMessage"
                        class="form-control"
                        id="specificMessage"
                        placeholder="Enter the message"
                    />
                </div>

                <!-- Answer the Specific Message Input -->
                <div class="mb-3">
                    <label for="answerMessage" class="form-label">Answer to the Specific Message</label>
                    <input
                        type="text"
                        v-model="answerMessage"
                        class="form-control"
                        id="answerMessage"
                        placeholder="Answer the message"
                    />
                </div>

                <!-- Submit Button -->
                <div class="text-center">
                    <button type="submit" class="btn btn-success">Submit</button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
/* Page styling */
body {
    background-color: #e6ffe6;
}

.form-page-container {
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

h1 {
    font-size: 2rem;
    font-weight: bold;
    color: #28a745;
}

.form-content .form-control {
    padding: 10px;
    border-radius: 10px;
}

.form-content .btn-success {
    background-color: #28a745;
    border: none;
    padding: 10px 20px;
    font-size: 1rem;
    font-weight: 600;
    transition: background-color 0.3s ease-in-out;
}

.form-content .btn-success:hover {
    background-color: #218838;
}
</style>
