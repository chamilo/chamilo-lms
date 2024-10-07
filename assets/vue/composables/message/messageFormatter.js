export function useMessageReceiverFormatter() {
  /**
   * @param {Object} message
   * @returns {Object[]}
   */
  function mapReceiverMixToUsers(message) {
    return [...message.receiversTo, ...message.receiversCc].map((receiver) => receiver.receiver)
  }

  /**
   * @param {Object[]} receiverList
   * @returns {Object[]}
   */
  function mapReceiverListToUsers(receiverList) {
    return receiverList.map((receiver) => receiver.receiver)
  }

  return {
    mapReceiverMixToUsers,
    mapReceiverListToUsers,
  }
}
