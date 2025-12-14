import React from 'react';

export default function StatusMessage({ tone, message }) {
  if (!tone) return null;
  const icons = {
    info: 'bi-info-circle',
    error: 'bi-exclamation-triangle',
    success: 'bi-check-circle',
  };

  return (
    <div className={`status status--${tone}`} role="alert">
      <i className={`bi ${icons[tone] || icons.info}`} aria-hidden="true" />
      <span>{message}</span>
    </div>
  );
}
