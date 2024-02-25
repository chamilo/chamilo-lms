import makeService from './api';
import { ENTRYPOINT } from "../config/entrypoint"

const legalExtensions = {
  async findAllByLanguage(languageId) {
    const params = new URLSearchParams({
      languageId: languageId,
      'order[version]': 'desc'
    });
    return fetch(`${ENTRYPOINT}legals?${params.toString()}`);
  },
  async saveOrUpdateLegal(payload) {
    console.log('Saving or updating legal terms');
    return fetch(`/legal/save`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });
  },
  async fetchExtraFields(termId = null) {
    try {
      const url = termId ? `/legal/extra-fields?termId=${termId}` : `/legal/extra-fields`;
      const response = await fetch(url);
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return await response.json();
    } catch (error) {
      console.error('Error loading extra fields:', error);
      throw error;
    }
  },
};

export default makeService('legals', legalExtensions);
