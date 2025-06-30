<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Prueba Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <main class="container mt-5">
    <section>
      <h1 class="text-center mb-4">Encuentra tu próximo destino</h1>
      <form class="row g-3 bg-light p-4 rounded shadow" method="GET" action="resultados.php">
        <div class="col-md-3">
          <label>Origen</label>
          <input type="text" name="origen" class="form-control" placeholder="Ej: Buenos Aires">
        </div>
        <div class="col-md-3">
          <label>Destino</label>
          <input type="text" name="destino" class="form-control" placeholder="Ej: París">
        </div>
        <div class="col-md-3">
          <label>Fecha de salida</label>
          <input type="date" name="fecha" class="form-control">
        </div>
        <div class="col-md-2">
          <label>Precio máximo</label>
          <input type="number" name="precio" class="form-control" placeholder="Ej: 2500">
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">Buscar</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
