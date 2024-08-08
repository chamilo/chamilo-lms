/**
 * See: src/CoreBundle/Entity/Message.php
 */

// Type indicating the message is in the user's inbox
export const MESSAGE_TYPE_INBOX = 1;

// Type indicating the message is in the user's outbox
export const MESSAGE_TYPE_OUTBOX = 2;

// Type indicating the message is promoted
export const MESSAGE_TYPE_PROMOTED = 3;

// Type indicating the message is posted on the user's wall
export const MESSAGE_TYPE_WALL = 4;

// Type indicating the message is sent to a group
export const MESSAGE_TYPE_GROUP = 5;

// Type indicating the message is an invitation
export const MESSAGE_TYPE_INVITATION = 6;

// Type indicating the message is part of a conversation
export const MESSAGE_TYPE_CONVERSATION = 7;

// Type indicating the message is sent by the sender and should appear in the sender's outbox
export const MESSAGE_TYPE_SENDER = 8;

// Status indicating the message is deleted
export const MESSAGE_STATUS_DELETED = 3;
