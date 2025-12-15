import React, { useEffect, useMemo, useState } from 'react';
import StatusMessage from './components/StatusMessage';

function initialForm() {
  return {
    legal_name: '',
    trade_name: '',
    country_id: '',
    default_currency_id: '',
    timezone: 'UTC',
    default_language: 'es',
    fee_rules: '',
    payment_cycle: {
      frequency: 'monthly',
      cutoff_day: 1,
      currency_id: '',
    },
  };
}

function contactFormDefaults() {
  return {
    name: '',
    email: '',
    phone: '',
    type: 'billing',
    is_primary: 0,
  };
}

export default function Tenants({ apiUrl }) {
  const [tenants, setTenants] = useState([]);
  const [selectedId, setSelectedId] = useState(null);
  const [detail, setDetail] = useState(null);
  const [form, setForm] = useState(initialForm);
  const [contactForm, setContactForm] = useState(contactFormDefaults);
  const [status, setStatus] = useState({ type: 'info', message: 'Cargando empresas...' });
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    refreshList();
  }, []);

  const selectedCounters = useMemo(() => detail?.counters || {}, [detail]);

  function refreshList() {
    fetch(`${apiUrl}/api/tenants`)
      .then((res) => res.json())
      .then((data) => {
        setTenants(data.data || []);
        setStatus({ type: 'success', message: 'Empresas sincronizadas' });
        if ((data.data || []).length && !selectedId) {
          pickTenant(data.data[0].id);
        }
      })
      .catch(() => setStatus({ type: 'error', message: 'No se pudo cargar el listado de empresas' }));
  }

  function pickTenant(id) {
    setSelectedId(id);
    setLoading(true);
    fetch(`${apiUrl}/api/tenants/${id}`)
      .then((res) => res.json())
      .then((data) => {
        setDetail(data.data);
        setForm((prev) => ({ ...prev, ...data.data, payment_cycle: data.data?.payment_cycle || prev.payment_cycle }));
        setStatus({ type: 'success', message: 'Detalle cargado' });
      })
      .catch(() => setStatus({ type: 'error', message: 'No se pudo cargar el detalle' }))
      .finally(() => setLoading(false));
  }

  function handleCreate(e) {
    e.preventDefault();
    setLoading(true);
    fetch(`${apiUrl}/api/tenants`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
    })
      .then((res) => res.json())
      .then((data) => {
        setStatus({ type: 'success', message: 'Empresa creada' });
        refreshList();
        if (data?.data?.id) {
          pickTenant(data.data.id);
        }
        setForm(initialForm());
      })
      .catch(() => setStatus({ type: 'error', message: 'No se pudo crear la empresa' }))
      .finally(() => setLoading(false));
  }

  function handleUpdate(e) {
    e.preventDefault();
    if (!selectedId) return;
    setLoading(true);
    fetch(`${apiUrl}/api/tenants/${selectedId}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
    })
      .then((res) => res.json())
      .then(() => {
        setStatus({ type: 'success', message: 'Empresa actualizada' });
        pickTenant(selectedId);
      })
      .catch(() => setStatus({ type: 'error', message: 'No se pudo actualizar la empresa' }))
      .finally(() => setLoading(false));
  }

  function toggleStatus(next) {
    if (!selectedId) return;
    const reason = window.prompt('Motivo de cambio de estado');
    setLoading(true);
    fetch(`${apiUrl}/api/tenants/${selectedId}/status`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: next, reason }),
    })
      .then((res) => res.json())
      .then(() => {
        setStatus({ type: 'success', message: 'Estado actualizado' });
        pickTenant(selectedId);
        refreshList();
      })
      .catch(() => setStatus({ type: 'error', message: 'No se pudo cambiar el estado' }))
      .finally(() => setLoading(false));
  }

  function saveContact(e) {
    e.preventDefault();
    if (!selectedId) return;
    setLoading(true);
    fetch(`${apiUrl}/api/tenants/${selectedId}/contacts`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(contactForm),
    })
      .then((res) => res.json())
      .then(() => {
        setStatus({ type: 'success', message: 'Contacto agregado' });
        pickTenant(selectedId);
        setContactForm(contactFormDefaults());
      })
      .catch(() => setStatus({ type: 'error', message: 'No se pudo guardar el contacto' }))
      .finally(() => setLoading(false));
  }

  function impersonate() {
    if (!selectedId) return;
    setLoading(true);
    fetch(`${apiUrl}/api/tenants/${selectedId}/impersonate`, { method: 'POST' })
      .then((res) => res.json())
      .then((data) => setStatus({ type: 'success', message: `Token de soporte: ${data.token} (expira ${data.expires_at})` }))
      .catch(() => setStatus({ type: 'error', message: 'No se pudo emitir token de soporte' }))
      .finally(() => setLoading(false));
  }

  function renderList() {
    return tenants.map((tenant) => (
      <button
        key={tenant.id}
        className={`tenant-pill ${tenant.id === selectedId ? 'active' : ''}`}
        onClick={() => pickTenant(tenant.id)}
      >
        <div>
          <p className="muted">{tenant.trade_name || tenant.legal_name}</p>
          <strong>{tenant.legal_name}</strong>
        </div>
        <span className={`badge badge--${tenant.status}`}>{tenant.status}</span>
      </button>
    ));
  }

  function renderContacts() {
    if (!detail?.contacts?.length) {
      return <p className="muted">Sin contactos aún.</p>;
    }
    return detail.contacts.map((contact) => (
      <div key={contact.id} className="contact-card">
        <div>
          <p className="muted">{contact.type}</p>
          <strong>{contact.name}</strong>
          <p>{contact.email}</p>
          {contact.phone && <p className="muted">{contact.phone}</p>}
        </div>
        {contact.is_primary ? <span className="badge">Principal</span> : null}
      </div>
    ));
  }

  function renderHistory(title, items, formatter) {
    if (!items || !items.length) return <p className="muted">Sin {title.toLowerCase()}.</p>;
    return (
      <ul className="history-list">
        {items.map((item) => (
          <li key={item.id}>
            <div>
              <strong>{formatter.title(item)}</strong>
              <p className="muted">{formatter.subtitle(item)}</p>
            </div>
            <span className="badge">{formatter.badge(item)}</span>
          </li>
        ))}
      </ul>
    );
  }

  return (
    <section className="tenants">
      <div className="header-row">
        <div>
          <p className="eyebrow">Empresas</p>
          <h2 className="heading">Tenants y contactos</h2>
          <p className="muted">Administra altas, configuración base y accesos temporales de soporte.</p>
        </div>
        <button className="ghost" onClick={refreshList} disabled={loading}>
          Refrescar
        </button>
      </div>

      <StatusMessage type={status.type} message={status.message} />

      <div className="tenants-grid">
        <div className="list-panel">
          <div className="list-header">
            <h3>Listado</h3>
            <span className="badge">{tenants.length} empresas</span>
          </div>
          <div className="tenant-list">{renderList()}</div>

          <div className="card bordered">
            <h4>Crear empresa</h4>
            <form className="form-grid" onSubmit={handleCreate}>
              <label>
                Razón social
                <input
                  required
                  value={form.legal_name}
                  onChange={(e) => setForm({ ...form, legal_name: e.target.value })}
                  placeholder="Spectra S.A."
                />
              </label>
              <label>
                Nombre comercial
                <input
                  value={form.trade_name}
                  onChange={(e) => setForm({ ...form, trade_name: e.target.value })}
                  placeholder="Marca"
                />
              </label>
              <label>
                País (ID)
                <input
                  required
                  value={form.country_id}
                  onChange={(e) => setForm({ ...form, country_id: e.target.value })}
                />
              </label>
              <label>
                Moneda base (ID)
                <input
                  required
                  value={form.default_currency_id}
                  onChange={(e) =>
                    setForm({ ...form, default_currency_id: e.target.value, payment_cycle: { ...form.payment_cycle, currency_id: e.target.value } })
                  }
                />
              </label>
              <label>
                Zona horaria
                <input
                  value={form.timezone}
                  onChange={(e) => setForm({ ...form, timezone: e.target.value })}
                />
              </label>
              <label>
                Idioma
                <select
                  value={form.default_language}
                  onChange={(e) => setForm({ ...form, default_language: e.target.value })}
                >
                  <option value="es">Español</option>
                  <option value="en">Inglés</option>
                </select>
              </label>
              <label>
                Frecuencia de pago
                <select
                  value={form.payment_cycle.frequency}
                  onChange={(e) => setForm({ ...form, payment_cycle: { ...form.payment_cycle, frequency: e.target.value } })}
                >
                  <option value="monthly">Mensual</option>
                  <option value="biweekly">Quincenal</option>
                </select>
              </label>
              <label>
                Día de corte
                <input
                  type="number"
                  value={form.payment_cycle.cutoff_day}
                  onChange={(e) => setForm({ ...form, payment_cycle: { ...form.payment_cycle, cutoff_day: Number(e.target.value) } })}
                />
              </label>
              <label className="full">
                Reglas de fees (JSON o texto)
                <textarea
                  value={form.fee_rules}
                  onChange={(e) => setForm({ ...form, fee_rules: e.target.value })}
                  placeholder='{"setup_fee":0,"fx_fee":0.01}'
                />
              </label>
              <button className="primary full" type="submit" disabled={loading}>
                Guardar empresa
              </button>
            </form>
          </div>
        </div>

        <div className="detail-panel">
          {detail ? (
            <div className="card bordered">
              <header className="detail-header">
                <div>
                  <p className="eyebrow">{detail.status === 'suspended' ? 'Suspendida' : 'Activa'}</p>
                  <h3>{detail.trade_name || detail.legal_name}</h3>
                  <p className="muted">ID: {detail.id}</p>
                </div>
                <div className="actions">
                  <button className="ghost" onClick={impersonate} disabled={loading}>
                    Impersonar (1h)
                  </button>
                  {detail.status === 'active' ? (
                    <button className="secondary" onClick={() => toggleStatus('suspended')} disabled={loading}>
                      Suspender
                    </button>
                  ) : (
                    <button className="primary" onClick={() => toggleStatus('active')} disabled={loading}>
                      Activar
                    </button>
                  )}
                </div>
              </header>

              <form className="form-grid" onSubmit={handleUpdate}>
                <label>
                  Razón social
                  <input value={form.legal_name || ''} onChange={(e) => setForm({ ...form, legal_name: e.target.value })} />
                </label>
                <label>
                  Nombre comercial
                  <input value={form.trade_name || ''} onChange={(e) => setForm({ ...form, trade_name: e.target.value })} />
                </label>
                <label>
                  País
                  <input value={form.country_id || ''} onChange={(e) => setForm({ ...form, country_id: e.target.value })} />
                </label>
                <label>
                  Moneda base
                  <input
                    value={form.default_currency_id || ''}
                    onChange={(e) => setForm({ ...form, default_currency_id: e.target.value })}
                  />
                </label>
                <label>
                  Zona horaria
                  <input value={form.timezone || ''} onChange={(e) => setForm({ ...form, timezone: e.target.value })} />
                </label>
                <label>
                  Idioma
                  <select
                    value={form.default_language || 'es'}
                    onChange={(e) => setForm({ ...form, default_language: e.target.value })}
                  >
                    <option value="es">Español</option>
                    <option value="en">Inglés</option>
                  </select>
                </label>
                <label>
                  Frecuencia de pago
                  <select
                    value={form.payment_cycle?.frequency || 'monthly'}
                    onChange={(e) => setForm({ ...form, payment_cycle: { ...form.payment_cycle, frequency: e.target.value } })}
                  >
                    <option value="monthly">Mensual</option>
                    <option value="biweekly">Quincenal</option>
                  </select>
                </label>
                <label>
                  Día de corte
                  <input
                    type="number"
                    value={form.payment_cycle?.cutoff_day || ''}
                    onChange={(e) => setForm({ ...form, payment_cycle: { ...form.payment_cycle, cutoff_day: Number(e.target.value) } })}
                  />
                </label>
                <label className="full">
                  Reglas de fees
                  <textarea
                    value={form.fee_rules || ''}
                    onChange={(e) => setForm({ ...form, fee_rules: e.target.value })}
                  />
                </label>
                <button className="primary" type="submit" disabled={loading}>
                  Actualizar
                </button>
              </form>

              <div className="stats-row">
                <div className="stat-box">
                  <p className="muted">Wallet</p>
                  <strong>{detail.wallet?.balance ?? 0} {detail.wallet?.currency_id || ''}</strong>
                </div>
                <div className="stat-box">
                  <p className="muted">Facturas</p>
                  <strong>{selectedCounters.invoices || 0}</strong>
                </div>
                <div className="stat-box">
                  <p className="muted">Nóminas</p>
                  <strong>{selectedCounters.payroll_runs || 0}</strong>
                </div>
                <div className="stat-box">
                  <p className="muted">Proyectos</p>
                  <strong>{selectedCounters.projects || 0}</strong>
                </div>
              </div>

              <div className="split">
                <div className="card light">
                  <h4>Contactos</h4>
                  {renderContacts()}
                  <form className="form-grid" onSubmit={saveContact}>
                    <label>
                      Nombre
                      <input
                        required
                        value={contactForm.name}
                        onChange={(e) => setContactForm({ ...contactForm, name: e.target.value })}
                      />
                    </label>
                    <label>
                      Correo
                      <input
                        required
                        value={contactForm.email}
                        onChange={(e) => setContactForm({ ...contactForm, email: e.target.value })}
                      />
                    </label>
                    <label>
                      Teléfono
                      <input
                        value={contactForm.phone}
                        onChange={(e) => setContactForm({ ...contactForm, phone: e.target.value })}
                      />
                    </label>
                    <label>
                      Tipo
                      <select
                        value={contactForm.type}
                        onChange={(e) => setContactForm({ ...contactForm, type: e.target.value })}
                      >
                        <option value="billing">Billing</option>
                        <option value="operations">Operaciones</option>
                        <option value="legal">Legal</option>
                        <option value="primary">Principal</option>
                      </select>
                    </label>
                    <label className="checkbox">
                      <input
                        type="checkbox"
                        checked={contactForm.is_primary === 1}
                        onChange={(e) => setContactForm({ ...contactForm, is_primary: e.target.checked ? 1 : 0 })}
                      />
                      Marcar como principal
                    </label>
                    <button className="secondary" type="submit" disabled={loading}>
                      Agregar contacto
                    </button>
                  </form>
                </div>

                <div className="card light">
                  <h4>Wallet e historial</h4>
                  <div className="pill-row">
                    <span className="badge">Zona horaria: {detail.timezone}</span>
                    <span className="badge">Idioma: {detail.default_language}</span>
                  </div>
                  <div className="stack">
                    <h5>Facturas</h5>
                    {renderHistory('Facturas', detail.invoices, {
                      title: (i) => `${i.invoice_number} · ${i.total} ${i.currency_id}`,
                      subtitle: (i) => i.due_at || i.issued_at || 'Sin fecha',
                      badge: (i) => i.status,
                    })}

                    <h5>Nómina</h5>
                    {renderHistory('Nómina', detail.payrolls, {
                      title: (i) => `${i.period_start} → ${i.period_end}`,
                      subtitle: (i) => `${i.total_amount} ${i.currency_id}`,
                      badge: (i) => i.status,
                    })}

                    <h5>Proyectos</h5>
                    {renderHistory('Proyectos', detail.projects, {
                      title: (i) => i.name,
                      subtitle: (i) => i.created_at,
                      badge: (i) => i.status,
                    })}

                    <h5>Contratos</h5>
                    {renderHistory('Contratos', detail.contracts, {
                      title: (i) => i.title || i.id,
                      subtitle: (i) => `${i.start_date || ''} ${i.end_date ? '→ ' + i.end_date : ''}`,
                      badge: (i) => i.status,
                    })}
                  </div>
                </div>
              </div>
            </div>
          ) : (
            <div className="card bordered">
              <p className="muted">Selecciona una empresa para ver el detalle.</p>
            </div>
          )}
        </div>
      </div>
    </section>
  );
}
