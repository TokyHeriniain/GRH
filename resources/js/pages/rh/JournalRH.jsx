import React, { useEffect, useState } from "react";
import axios from "axios";
import { Table, Form, Button, Spinner, Pagination } from "react-bootstrap";
import NavigationLayout from "../../components/NavigationLayout";

export default function JournalRH() {
  const [rows, setRows] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    action: "",
    from: "",
    to: "",
  });
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({});

  const fetchData = () => {
    setLoading(true);
    axios
      .get("/api/rh/journal", {
        params: { ...filters, page },
      })
      .then((res) => {
        setRows(res.data.data);
        setMeta(res.data);
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    fetchData();
  }, [page]);

  return (
    <NavigationLayout>
      <h4 className="mb-3">🧾 Journal RH (Audit)</h4>

      {/* FILTRES */}
      <Form className="row g-2 mb-3">
        <Form.Group className="col-md-3">
          <Form.Select
            value={filters.action}
            onChange={(e) =>
              setFilters({ ...filters, action: e.target.value })
            }
          >
            <option value="">-- Action --</option>
            <option value="CREATE">Création</option>
            <option value="UPDATE">Modification</option>
            <option value="APPROVE">Approbation</option>
            <option value="REJECT">Refus</option>
            <option value="CLOTURE_ANNUELLE_GLOBALE">Clôture annuelle</option>
          </Form.Select>
        </Form.Group>

        <Form.Group className="col-md-2">
          <Form.Control
            type="date"
            value={filters.from}
            onChange={(e) =>
              setFilters({ ...filters, from: e.target.value })
            }
          />
        </Form.Group>

        <Form.Group className="col-md-2">
          <Form.Control
            type="date"
            value={filters.to}
            onChange={(e) =>
              setFilters({ ...filters, to: e.target.value })
            }
          />
        </Form.Group>

        <div className="col-md-2">
          <Button onClick={() => fetchData()}>🔍 Filtrer</Button>
        </div>

        <div className="col-md-3 text-end">
          <Button
            variant="success"
            className="me-2"
            onClick={() =>
              window.open("/api/rh/journal/export/excel")
            }
          >
            📊 Excel
          </Button>
          <Button
            variant="secondary"
            onClick={() =>
              window.open("/api/rh/journal/export/pdf")
            }
          >
            📄 PDF
          </Button>
        </div>
      </Form>

      {/* TABLE */}
      {loading ? (
        <div className="text-center my-4">
          <Spinner animation="border" />
        </div>
      ) : (
        <Table bordered hover size="sm">
          <thead className="table-light">
            <tr>
              <th>Date</th>
              <th>Action</th>
              <th>Personnel</th>
              <th>Acteur</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.id}>
                <td>{r.date}</td>
                <td>{r.action}</td>
                <td>{r.personnel || "-"}</td>
                <td>{r.acteur}</td>
                <td>{r.ip}</td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}

      {/* PAGINATION */}
      <Pagination>
        {[...Array(meta.last_page || 1)].map((_, i) => (
          <Pagination.Item
            key={i}
            active={i + 1 === page}
            onClick={() => setPage(i + 1)}
          >
            {i + 1}
          </Pagination.Item>
        ))}
      </Pagination>
    </NavigationLayout>
  );
}
