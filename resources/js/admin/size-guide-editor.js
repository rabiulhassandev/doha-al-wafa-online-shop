/**
 * Editable size guide table: measurement columns across the top, one row per size.
 * Every row keeps one value per column, so adding or removing a column updates each row.
 */
export default (initialColumns = [], initialRows = []) => ({
    columns: initialColumns.length > 0 ? initialColumns : [{ en: '', ar: '' }],
    rows: initialRows.length > 0
        ? initialRows.map((row) => ({ size: row.size ?? '', values: [...(row.values ?? [])] }))
        : [{ size: '', values: [''] }],

    init() {
        this.rows.forEach((row) => this.fitValues(row));
    },

    addColumn() {
        this.columns.push({ en: '', ar: '' });
        this.rows.forEach((row) => row.values.push(''));
    },

    removeColumn(index) {
        if (this.columns.length === 1) {
            return;
        }

        this.columns.splice(index, 1);
        this.rows.forEach((row) => row.values.splice(index, 1));
    },

    addRow() {
        this.rows.push({ size: '', values: this.columns.map(() => '') });
    },

    removeRow(index) {
        if (this.rows.length === 1) {
            return;
        }

        this.rows.splice(index, 1);
    },

    fitValues(row) {
        while (row.values.length < this.columns.length) {
            row.values.push('');
        }

        row.values.length = this.columns.length;
    },
});
