<template>
  <div class="card">
    <div class="card-header">Lista de Empresas</div>
    <div class="card-body">
      <el-table :data="tableData" style="width: 100%">
        <el-table-column prop="key" label="#" width="80"></el-table-column>
        <el-table-column prop="identification_number" label="NIT" width="150"></el-table-column>
        <el-table-column prop="name" label="Empresa" width="200"></el-table-column>
        <el-table-column prop="email" label="Email" width="200"></el-table-column>
        <el-table-column prop="created_at" label="Fecha" width="180"></el-table-column>
        <el-table-column fixed="right" label="Acciones" width="200">
          <template slot-scope="scope">
            <a 
              :href="`/documents?company=${scope.row.identification_number}&name=${encodeURIComponent(scope.row.name)}`" 
              class="btn btn-sm btn-primary"
            >
              <i class="fa fa-file-alt"></i> Ver Documentos
            </a>
          </template>
        </el-table-column>
      </el-table>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      resource: "configuration",
      tableData: []
    };
  },
  created() {
    this.getRecords();
  },
  methods: {
    getFilterRecord(array) {
      return array.filter(function(x) {
        return x.identification_number != null;
      });
    },
    getRecords() {
      this.$http
        .get(`/${this.resource}/records`)
        .then(response => {
          this.tableData = this.getFilterRecord(response.data.data);
        })
        .catch(error => {
          console.error("Error:", error);
        });
    }
  }
};
</script>
