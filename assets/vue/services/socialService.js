import axios from 'axios';

const API_URL = '/social-network';

export default {
  async fetchPersonalData(userId) {
    try {
      const response = await axios.get(`${API_URL}/personal-data/${userId}`);
      return response.data.personalData;
    } catch (error) {
      console.error('Error fetching personal data:', error);
      throw error;
    }
  },

  async fetchTermsAndConditions(userId) {
    try {
      const response = await axios.get(`${API_URL}/terms-and-conditions/${userId}`);
      return response.data.terms;
    } catch (error) {
      console.error('Error fetching terms and conditions:', error);
      throw error;
    }
  },

  async fetchLegalStatus(userId) {
    try {
      const response = await axios.get(`${API_URL}/legal-status/${userId}`);
      return response.data;
    } catch (error) {
      console.error('Error fetching legal status:', error);
      throw error;
    }
  },

  async submitPrivacyRequest({ userId, explanation, requestType }) {
    try {
      const response = await axios.post(`${API_URL}/handle-privacy-request`, {
        explanation,
        userId,
        requestType,
      });
      return response.data;
    } catch (error) {
      console.error('Error submitting privacy request:', error);
      throw error;
    }
  },

  async submitAcceptTerm(userId) {
    try {
      const response = await axios.post(`${API_URL}/send-legal-term`, {
        userId,
      });
      return response.data;
    } catch (error) {
      console.error('Error accepting the term:', error);
      throw error;
    }
  },
};
