import { defineBoot } from '#q-app/wrappers'
import imageSkeleton from 'src/directives/imageSkeleton'
import LoadingSkeleton from 'src/components/LoadingSkeleton.vue'

export default defineBoot(({ app }) => {
  app.directive('image-skeleton', imageSkeleton)
  app.component('LoadingSkeleton', LoadingSkeleton)
})
