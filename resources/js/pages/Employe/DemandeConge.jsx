import React, { useEffect, useState } from "react";
import api from "../../axios";
import { Form, Button, Card, Alert, Nav } from "react-bootstrap";
import NavigationLayout from "../../components/NavigationLayout";

export default function DemandeConge() {
  const [types, setTypes] = useState([]);
  const [soldes, setSoldes] = useState([]);
  const [form, setForm] = useState({
    leave_type_id: "",
    date_debut: "",
    date_fin: "",
    heure_debut: "08:00",
    heure_fin: "17:00",
    raison: "",
  });

  const [message, setMessage] = useState(null);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    const [typesRes, soldesRes] = await Promise.all([
      api.get("/api/leave-types-search"),
      api.get("/api/me/soldes"),
    ]);

    setTypes(typesRes.data || []);
    setSoldes(soldesRes.data || []);
  };

  const submit = async (e) => {
    e.preventDefault();
    try {
      await api.post("/api/me/conges", form);
      setMessage("Demande envoyée avec succès ✅");
    } catch (err) {
      setMessage("Erreur lors de la demande ❌");
    }
  };

  return (
    <NavigationLayout>
    <Card className="p-4 shadow-sm">
      <h5>Nouvelle demande de congé</h5>

      {message && <Alert>{message}</Alert>}

      <Form onSubmit={submit}>
        <Form.Select
          value={form.leave_type_id}
          onChange={(e) =>
            setForm({ ...form, leave_type_id: e.target.value })
          }
        >
          <option value="">Type de congé</option>
          {types.map((t) => (
            <option key={t.id} value={t.id}>
              {t.nom}
            </option>
          ))}
        </Form.Select>

        <Form.Control
          type="date"
          value={form.date_debut}
          onChange={(e) =>
            setForm({ ...form, date_debut: e.target.value })
          }
        />

        <Form.Control
          type="date"
          value={form.date_fin}
          onChange={(e) =>
            setForm({ ...form, date_fin: e.target.value })
          }
        />

        <Form.Control
          as="textarea"
          placeholder="Raison"
          value={form.raison}
          onChange={(e) =>
            setForm({ ...form, raison: e.target.value })
          }
        />

        <Button className="mt-3" type="submit">
          Envoyer la demande
        </Button>
      </Form>

      <hr />

      <h6>Mes soldes</h6>
      {soldes.map((s) => (
        <div key={s.type}>
          {s.type} : <b>{s.solde} jours</b>
        </div>
      ))}
    </Card>
    </NavigationLayout>
    
  );
}
