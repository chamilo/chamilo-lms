<template>
    <div
            class="alert alert-danger"
            role="alert"
    >
        {{ error.response.data.error }}
    </div>
</template>

<script>
    export default {
        name: "ErrorMessage",
        props: {
            error: {
                type: Error,
                required: true
            }
        },
    }
</script>